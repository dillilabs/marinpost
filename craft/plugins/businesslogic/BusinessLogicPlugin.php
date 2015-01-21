<?php
namespace Craft;

class BusinessLogicPlugin extends BasePlugin
{

    public function getName()
    {
        return 'Business Logic for '.craft()->getSiteName();
    }

    public function getVersion()
    {
        return '¯\_(ツ)_/¯';
    }

    public function getDeveloper()
    {
        return craft()->getSiteName();
    }

    public function getDeveloperUrl()
    {
        return 'https://github.com/lindseydiloreto/craft-businesslogic';
    }
    
    /* Hook to display entry.author in entry list
     */
    public function modifyEntryTableAttributes(&$attributes, $source)
    {
        $attributes['author'] = Craft::t('Author');
    }

    /* Hook to display entry.author.name in entry list
     */
    public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
    {
        if ($attribute == 'author')
        {
            return $entry->author->name;
        }
    }

    /* Listen to events
     */
    public function init()
    {
        // Event users.onSaveuser:
        //   Keep {first,last}Name fields synchronized with name{First,Last} fields
        //   The former fields are: "special", not required, hidden in Account tab by JS
        //   The latter fields are: custom, required, editable in Profile tab
        craft()->on('users.onSaveUser', function(Event $event) {
            $user = $event->params['user'];
            if (strcmp($user->firstName, $user->nameFirst) !== 0 || strcmp($user->lastName, $user->nameLast) !== 0) {
		    $user->firstName = $user->nameFirst;
		    $user->lastName = $user->nameLast;
		    self::log('users.onSaveUser: updating '.$user->email.'='.$user->name, LogLevel::Info);
		    craft()->users->saveUser($user);
            }

            if (! craft()->userSession->user->admin) {
		    foreach (craft()->dashboard->userWidgets as $widget) {
			    if ($widget->type !== 'RecentEntries') {
				    self::log($widget->type.' id='.$widget->id);
				    // craft()->dashboard->deleteUserWidgetById($widget->id);
			    }
		    }
            }
        });
    }
}
