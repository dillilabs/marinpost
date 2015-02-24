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

        if ($this->_isCp()) {
            $this->_includeJs();
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
     * Keep {first,last}Name fields synchronized with name{First,Last} fields.
     *
     *  The former fields are:
     *
     *      "special"
     *      not required
     *      hidden in Account tab by JS
     *
     *  The latter fields are:
     *
     *      custom
     *      required
     *      editable in Profile tab
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
     * Add Javascript to the Control Panel:
     *
     *  For all Users:
     *
     *      Remove First Name, Last Name and Week Start Day fields on user My Account Account tab
     */
    private function _includeJs() {
        $js = <<<'JS'
var userFormFields = $('form#userform .field');
userFormFields.filter('#firstName-field, #lastName-field, #weekStartDay-field').remove();
JS;

        craft()->templates->includeJs($js);
    }

    //----------------------
    // Helper functions
    //----------------------

    /**
     * Return true if control panel
     */
    private function _isCp() {
        return craft()->request->isCpRequest();
    }

    /**
     * Return true if logged-in.
     */
    private function _isLoggedIn() {
        return craft()->userSession->isLoggedIn();
    }

    /**
     * Return true if logged-in admin user.
     */
    private function _isAdmin() {
        return $this->_isLoggedIn() && craft()->userSession->user->admin;
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
        return '0.0.12';
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
