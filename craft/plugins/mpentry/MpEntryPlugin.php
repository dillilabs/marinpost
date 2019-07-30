<?php
namespace Craft;

class MpEntryPlugin extends BasePlugin
{
    private $settings;

    /**
     * Initialization:
     *
     *  If front-end request:
     *
     *    Listen to entries.onBeforeSaveEntry event
     *
     *  Listen to entries.onSaveEntry event
     */
    public function init()
    {
        parent::init();

        $this->settings = $this->getSettings();

        if (!craft()->request->isCpRequest())
        {
            $this->_onBeforeSaveEntryEvent();
        }

        $this->_onSaveEntryEvent();
    }

    /**
     * Semi-smart logger.
     */
    public function logger($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    //--------------------------------------------------------------------------
    // Event functions
    //--------------------------------------------------------------------------

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
            $entry = $event->params['entry'];
            $isNew = $event->params['isNewEntry'];
            $this->logger("entries.onBeforeSaveEntry BEGIN: $entry [ {$entry->id}]");

            if ($entry->status == 'disabled')
            {
                if (!craft()->mpEntry->isValidEntry($entry))
                {
                    $this->logger("entries.onBeforeSaveEntry: Invalid (disabled) entry: $entry [{$entry->id}]");
                    $event->performAction = false;
                    return;
                }
            }

            if ($this->_author()->isInGroup('guest') && $entry->section->handle != 'ad')
            {
                if (craft()->mpEntry->isPublishedEntry($entry))
                {
                    $this->logger("entries.onBeforeSaveEntry: Guest may not update published entry: $entry [{$entry->id}]");
                    $entry->addError('title', 'You may not update a published entry.');
                    $event->performAction = false;
                    return;
                }
            }

            $this->logger("entries.onBeforeSaveEntry END: $entry [ {$entry->id}]");
        });
    }

    /**
     * Respond to entries.onSaveEntry event.
     *
     * If entry section is blog, media, news or notices:
     *
     *   Then:
     *
     *      manage the hidden, "child" Locations of the entry's selected Locations
     *
     *      manage the Tags from the tag string
     *
     *      update the Craft search index
     */
    private function _onSaveEntryEvent()
    {
        craft()->on('entries.onSaveEntry', function(Event $event) {
            $entry = $event->params['entry'];

            // TODO refactor to plugin settings
            $sections = array('blog', 'media', 'news', 'notices');

            if (in_array($entry->section->handle, $sections))
            {
                craft()->mpEntry->synchronizeChildLocations($entry);
                craft()->mpEntry->synchronizeTags($entry);
                craft()->mpEntry->updateSearchIndex($entry);
            }
        });
    }

    //--------------------------------------------------------------------------
    // Helper functions
    //--------------------------------------------------------------------------

    private function _author()
    {
        return craft()->userSession->isLoggedIn() ? craft()->userSession->user : null;
    }

    //--------------------------------------------------------------------------
    // Settings
    //--------------------------------------------------------------------------

    protected function defineSettings()
    {
        return array(
            'defaultEntryLimit'         => array(AttributeType::String, 'default' => 10),
            'forceLog'                  => array(AttributeType::Bool,   'default' => false),
            'googleAnalyticsTrackingId' => array(AttributeType::String, 'default' => ''),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('mpentry/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //--------------------------------------------------------------------------
    // Boilerplate functions
    //--------------------------------------------------------------------------

    public function getName()
    {
        return 'Marin Post Entries';
    }

    public function getVersion()
    {
        return '1.2.4';
    }

    public function getDeveloper()
    {
        return 'Steve Pedersen';
    }

    public function getDeveloperUrl()
    {
        return 'https://github.com/speder';
    }

    //--------------------------------------------------------------------------
    // Twig
    //--------------------------------------------------------------------------

    public function addTwigExtension()
    {
        Craft::import('plugins.mpentry.twigextensions.MpEntryTwigExtension');

        return new MpEntryTwigExtension();
    }

}
