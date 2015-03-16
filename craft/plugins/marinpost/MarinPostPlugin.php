<?php
namespace Craft;

class MarinPostPlugin extends BasePlugin
{
    private $settings;

    /**
     * Initialization:
     *
     *  Listen to entries.onBeforeSaveEntry event
     *
     *  Listen to users.onBeforeSaveUser event
     */
    public function init()
    {
        parent::init();
        $this->settings = $this->getSettings();

        $this->_onBeforeSaveEntryEvent();
        $this->_onBeforeSaveUserEvent();
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
     *
     *  If request is from the front-end:
     *
     *      If the entry is disabled:
     *
     *          Then validate the entry and prevent save if invalid.
     *
     *      If the author is a Guest and the entry has already been published:
     *
     *          Then prevent save.
     */
    private function _onBeforeSaveEntryEvent()
    {
        craft()->on('entries.onBeforeSaveEntry', function(Event $event) {
            if (!craft()->request->isCpRequest()) {
                $entry = $event->params['entry'];
                $isNew = $event->params['isNewEntry'];

                if ($entry->status == 'disabled')
                {
                    if (!$this->_validEntry($entry))
                    {
                        $this->_log('Invalid disabled entry: ' . $isNew ? 'new' : $entryId);
                        $event->performAction = false;
                        return;
                    }

                    if ($this->_author()->isInGroup('guest') && $this->_publishedEntry($entry))
                    {
                        if ($this->_publishedEntry($entry))
                        {
                            $this->_log('Cannot update published entry: ' . $entry->id);
                            $entry->addError('title', 'Cannot update published entry');
                            $event->performAction = false;
                            return;
                        }
                    }

                }
            }
        });
    }

    /**
     * Respond to users.onBeforeSaveUser event.
     *
     *  If User firstName or lastName is blank:
     *
     *      Then add error(s) and prevent save.
     */
    private function _onBeforeSaveUserEvent()
    {
        craft()->on('users.onBeforeSaveUser', function(Event $event) {
            $user = $event->params['user'];

            $firstName = craft()->request->getPost('firstName', $user->firstName);
            $lastName = craft()->request->getPost('lastName', $user->lastName);

            $valid = true;

            if (empty(trim($firstName)))
            {
                $user->addError('firstName', 'First name cannot be blank.');
                $valid= false;
            }

            if (empty(trim($lastName)))
            {
                $user->addError('lastName', 'Last name cannot be blank.');
                $valid= false;
            }

            if (!$valid)
            {
                $this->_log('Invalid user: '.$user->username);
                $event->performAction = false;
            }
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
     * Return true if entry has already been published.
     */
    private function _publishedEntry($entry)
    {
        if (!$entry->id) return false;
        $originalEntry = craft()->entries->getEntryById($entry->id);
        return $originalEntry->status == 'live';
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


    private function _author()
    {
        return craft()->userSession->isLoggedIn() ? craft()->userSession->user : null;
    }

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
        return '0.0.16';
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
