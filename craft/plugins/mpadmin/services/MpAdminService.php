<?php
namespace Craft;

class MpAdminService extends BaseApplicationComponent
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpadmin');
    }

    /**
     * Return true if text contains trigger words.
     */
    public function containsTriggerWords($text)
    {
        $tokens = array_map(function($s) { return trim($s); }, explode(',', $this->plugin->settings->triggerWords));

        // remove empty entries
        $tokens = array_filter($tokens);
        if (empty($tokens))
        {
            return false;
        }

        $re = '('.implode('|', $tokens).')i';

        return preg_match($re, strip_tags($text));
    }

    /**
     * Return text from Entry content.
     */
    public function entryText($entry)
    {
        $text = $entry->title;

        switch ($entry->section->handle)
        {
        case 'blog':
            $text .= $entry->blogContent;
            break;
        case 'letters':
            $text .= $entry->letterContent;
            break;
        case 'media':
        case 'news':
            $text .= $entry->linkComments;
            break;
        case 'notices':
            $text .= $entry->noticeContent;
            break;
        }

        return strip_tags($text);
    }

    /**
     * Return text from User field content.
     */
    public function userText($user)
    {
        return strip_tags($user->bio);
    }

    /**
     * Notify moderator of apparently offensive language.
     */
    public function notifyModerator($element)
    {
        switch ($element->elementType)
        {
        case 'Entry':
            $message = "{$element->section} entry by {$element->author->fullName} ({$element->author}) entitled \"{$element->title}\"";
            break;
        case 'User':
            $message = "The profile of {$element->fullName} ({$element->email})";
            break;
        default:
            $message = "{$element->elementType} entitled '{$element->title}'";
        }
        $message .= ' appears to contain offensive language.';

        $host = craft()->request->hostName;

        $email = new EmailModel();
        $emailSettings = craft()->email->getSettings();

        $email->fromEmail = $emailSettings['emailAddress'];
        $email->replyTo   = $emailSettings['emailAddress'];
        $email->sender    = $emailSettings['emailAddress'];
        $email->fromName  = $emailSettings['senderName'];
        $email->toEmail   = $emailSettings['emailAddress'];
        $email->subject   = "Apparently offensive language warning on $host";
        $email->body      = $message;

        craft()->email->sendEmail($email);

        $this->plugin->logger($message, LogLevel::Warning);
    }
}