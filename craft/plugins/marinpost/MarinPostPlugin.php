<?php
namespace Craft;

class MarinPostPlugin extends BasePlugin
{
    private $settings;

    /**
     * Class init
     */
    public function init()
    {
        parent::init();

        $this->settings = $this->getSettings();

        // respond to entries.onBeforeSaveEntry event
        $this->_onBeforeSaveEntryEvent();

        // respond to users.onSaveUser event
        $this->_onSaveUserEvent();

        if (craft()->request->isCpRequest()) {
            // inject Javascript into the Control Panel
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
     * Respond to entries.onBeforeSaveEntry event.
     */
    private function _onBeforeSaveEntryEvent()
    {
        craft()->on('entries.onBeforeSaveEntry', function(Event $event) {
            $entry = $event->params['entry'];
            $isNewEntry = $event->params['isNewEntry'];

            if ($entry->status == 'disabled')
            {
                $this->_log('Validating disabled entry: ' . ($isNewEntry ? 'new' : $entry->id));

                if (! $this->_validEntry($entry))
                {
                    $this->_log('Invalid disabled entry: ' . ($isNewEntry ? 'new' : $entry->id));

                    $event->performAction = false;
                }
            }
        });
    }

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

    //----------------------
    // Event helper functions
    //----------------------

    /**
     * Validate disabled entry, add errors and return false if invalid.
     */
    private function _validEntry($disabledEntry)
    {
        if (craft()->content->validateContent($disabledEntry))
        {
            return true;
        }
        else
        {
            $disabledEntry->addErrors($disabledEntry->getContent()->getErrors());
            return false;
        }
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

    //
    // Settings
    //

    protected function defineSettings()
    {
        return array(
            'defaultEntryLimit' => array(AttributeType::String, 'default' => 10),
            'forceLog' => array(AttributeType::Bool, 'default' => false),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('marinpost/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    // ----------------
    // Helper functions
    // ----------------

    private function _log($mixed, $level = LogLevel::Info)
    {
        $message = is_array($mixed) ? json_encode($mixed) : $mixed;

        self::log($message, $level, $this->settings['forceLog']);
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
        return '0.0.15';
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
