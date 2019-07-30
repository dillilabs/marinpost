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

        $email = new EmailModel();
        $emailSettings = craft()->email->getSettings();

        $email->fromEmail = $emailSettings['emailAddress'];
        $email->replyTo   = $emailSettings['emailAddress'];
        $email->sender    = $emailSettings['emailAddress'];
        $email->fromName  = $emailSettings['senderName'];
        $email->toEmail   = $emailSettings['emailAddress'];
        if (!empty($this->plugin->settings->moderatorEmail))
        {
            $email->cc    = array(array('name' => 'Marin Post Moderator', 'email' => $this->plugin->settings->moderatorEmail));
        }
        $email->subject   = "Apparently offensive language warning on ".craft()->request->hostName;
        $email->body      = $message;

        craft()->email->sendEmail($email);

        $this->plugin->logger($message, LogLevel::Warning);
    }

    /**
     * Email admin on server error.
     */
    public function notifyAdminOfServerError($errorMessage)
    {

        $body = "Server Error Message: $errorMessage";
        $body .= "\n\nURL: ".craft()->request->url.' ['.craft()->request->requestType.']';
        if (!empty(craft()->request->urlReferrer))
        {
            $body .= "\n\nReferrer: ".craft()->request->urlReferrer;
        }
        $body .= "\n\nUser Agent: ".craft()->request->userAgent;
        $body .= "\n\nUser IP Address: ".craft()->request->ipAddress;
        if ($user = craft()->userSession->user)
        {
            $body .= "\n\nUser Name: {$user->fullName}";
            $body .= "\n\nUser Email Address: {$user->email}";
        }

        $email = new EmailModel();
        $emailSettings = craft()->email->getSettings();

        $email->fromEmail = $emailSettings['emailAddress'];
        $email->replyTo   = $emailSettings['emailAddress'];
        $email->sender    = $emailSettings['emailAddress'];
        $email->fromName  = $emailSettings['senderName'];
        $email->toEmail   = $emailSettings['emailAddress'];
        if (!empty($this->plugin->settings->adminEmail))
        {
            $email->cc    = array(array('name' => 'Marin Post Admin', 'email' => $this->plugin->settings->adminEmail));
        }
        $email->subject   = 'Server Error on '.craft()->request->hostName;
        $email->body      = $body;

        craft()->email->sendEmail($email);

        $this->plugin->logger($body, LogLevel::Warning);
    }

    /**
     * Notify admin of published entry -- for spam purposes.
     */
    public function notifyAdminOfPublishedEntry($entry)
    {
        $savePath = craft()->path->getTemplatesPath();
        craft()->path->setTemplatesPath(craft()->path->getPluginsPath().'mpadmin/templates');
        $body = craft()->templates->render('entry', array('entry' => $entry));
        craft()->path->setTemplatesPath($savePath);

        $email            = new EmailModel();
        $emailSettings    = craft()->email->getSettings();
        $email->fromEmail = $emailSettings['emailAddress'];
        $email->replyTo   = $emailSettings['emailAddress'];
        $email->sender    = $emailSettings['emailAddress'];
        $email->fromName  = $emailSettings['senderName'];
        $email->toEmail   = $emailSettings['emailAddress'];
        $section          = ucfirst($entry->section->handle);
        $email->subject   = "$section entry published on ".craft()->request->hostName;
        if($section == 'Ad'){
            $email->subject   = "$section entry paid for on ".craft()->request->hostName;
        }
        $email->htmlBody  = $body;

        craft()->email->sendEmail($email);
    }

    /**
     * Notify admin of an ad submitted for review entry
     */
    public function notifyAdminOfSubmittedAdEntry($entry)
    {
        $savePath = craft()->path->getTemplatesPath();
        craft()->path->setTemplatesPath(craft()->path->getPluginsPath().'mpadmin/templates');
        $body = craft()->templates->render('entry', array('entry' => $entry));
        craft()->path->setTemplatesPath($savePath);

        $email            = new EmailModel();
        $emailSettings    = craft()->email->getSettings();
        $email->fromEmail = $emailSettings['emailAddress'];
        $email->replyTo   = $emailSettings['emailAddress'];
        $email->sender    = $emailSettings['emailAddress'];
        $email->fromName  = $emailSettings['senderName'];
        $email->toEmail   = $emailSettings['emailAddress'];
        $section          = ucfirst($entry->section->handle);
        $email->subject   = "$section entry submitted for review on ".craft()->request->hostName;
        $email->htmlBody  = $body;

        craft()->email->sendEmail($email);
    }

    /**
     * Notify user of an ad approval and send request to pay.
     */
    public function notifyUserOfAdApproval($entry)
    {
        $savePath = craft()->path->getTemplatesPath();
        craft()->path->setTemplatesPath(craft()->path->getPluginsPath().'mpadmin/templates');
        $body = craft()->templates->render('adapproval', array('entry' => $entry, 'hostname' => craft()->request->hostName));
        craft()->path->setTemplatesPath($savePath);

        $email            = new EmailModel();
        $emailSettings    = craft()->email->getSettings();
        $email->fromEmail = $emailSettings['emailAddress'];
        $email->replyTo   = $emailSettings['emailAddress'];
        $email->sender    = $emailSettings['emailAddress'];
        $email->fromName  = $emailSettings['senderName'];
        
        $email->toEmail   = $entry->getAuthor()->email;
        $section          = ucfirst($entry->section->handle);
        $email->subject   = "$section entry submitted for review on ".craft()->request->hostName." has been approved";
        $email->htmlBody  = $body;

        craft()->email->sendEmail($email);
    }

    /**
     * Return true if current User is an admin assistant.
     */
    public function isAdminOrAdminAssistant()
    {
        return (craft()->userSession->isAdmin() || craft()->userSession->getUser()->isInGroup('adminAssistant'));
    }

    /**
     * Throw 403 unless User is an admin or admin assistant.
     */
    public function requireAdminOrAdminAssistant()
    {
        if (!$this->isAdminOrAdminAssistant())
        {
            throw new HttpException(403, Craft::t('This action may only be performed by admins or admin assitants.'));
        }
    }
}
