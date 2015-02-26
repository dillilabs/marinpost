<?php
namespace Craft;

class MarinPostPlugin extends BasePlugin
{
    /**
     * Class init
     */
    public function init()
    {
        parent::init();

        $this->_onSaveUserEvent();

        if (craft()->request->isCpRequest()) {
            $this->_includeCpJs();
        }
    }

    //----------------------
    // Hook functions
    //----------------------

    /**
     * Modify columns included in Control Panel entry list.
     */
    public function modifyEntryTableAttributes(&$attributes, $source)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        $attributes['author'] = Craft::t('Author');
    }

    /**
     * Display of columns in Control Panel entry list.
     */
    public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
    {
        if (defined($attribute) && $attribute == 'author')
        {
            return $entry->author->name;
        }
    }

    /**
     * Modify sort by columns in Control Panel entry list.
     */
    public function modifyEntrySortableAttributes(&$attributes)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        // $attributes['author'] = Craft::t('Author');
    }

    //----------------------
    // Event functions
    //----------------------

    /**
     * Respond to the users.onSaveUser event.
     */
    private function _onSaveUserEvent()
    {
        craft()->on('users.onSaveUser', function(Event $event) {
            $user = $event->params['user'];
            $this->_syncUserName($user);
        });
    }

    /**
     * Keep (native) firstName and lastName fields synchronized with (custom) nameFirst and nameLast fields.
     *
     *  The native fields are:
     *
     *      not required
     *      hidden in User Account tab via Javascript (by this plugin)
     *      "special"
     *
     *  The custom fields are:
     *
     *      required
     *      editable in User Profile tab
     *
     *  Synchronization is desirable because the native fields are ubiquitously used to display the User name.
     */
    private function _syncUserName($user) {
        if (strcmp($user->firstName, $user->nameFirst) !== 0 || strcmp($user->lastName, $user->nameLast) !== 0) {
            $user->firstName = $user->nameFirst;
            $user->lastName = $user->nameLast;
            self::log("Synchronizing first and last name of {$user->name} ({$user->email})", LogLevel::Warning);
            craft()->users->saveUser($user);
        }
    }

    //----------------------
    // Universal CP functions
    //----------------------

    /**
     * Add Javascript to the Control Panel to do the following:
     *
     *  In User Account tab remove the following fields:
     *
     *      First Name
     *      Last Name
     *      Week Start Day
     */
    private function _includeCpJs() {
        $js = <<<'JS'
var userFormFields = $('form#userform .field');
userFormFields.filter('#firstName-field, #lastName-field, #weekStartDay-field').remove();
JS;

        craft()->templates->includeJs($js);
    }

    //----------------------
    // Boilerplate functions
    //----------------------

    public function getName()
    {
        return 'Marin Post';
    }

    public function getVersion()
    {
        return '0.0.13';
    }

    public function getDeveloper()
    {
        return 'Steve Pedersen';
    }

    public function getDeveloperUrl()
    {
        return 'https://github.com/speder';
    }
}
