<?php
namespace Craft;

class MpEntryPlugin extends BasePlugin
{
    private $settings;

    /**
     * Initialization:
     *
     *  Listen to entries.onBeforeSaveEntry event
     */
    public function init()
    {
        parent::init();
        $this->settings = $this->getSettings();
        $this->_onBeforeSaveEntryEvent();
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
        return craft()->templates->render('mpentry/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //----------------------
    // Boilerplate functions
    //----------------------

    public function getName()
    {
        return 'Marin Post Entries';
    }

    public function getVersion()
    {
        return '0.0.18';
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
