<?php
namespace Craft;

class MpAdminPlugin extends BasePlugin
{
    /**
     *  If CP request:
     *
     *      Inject Javascript to ensure only a single User group is selected per User.
     *
     *  Else:
     *
     *      Listen to onSaveEntry event.
     *
     *      Listen to onSaveUser event.
     */
    public function init()
    {
        parent::init();

        $this->settings = $this->getSettings();

        if (craft()->request->isCpRequest())
        {
            $this->_ensureOneUserGroup();
        }
        else
        {
            $this->_onSaveEntryEvent();
            $this->_onSaveUserEvent();
        }
    }

    public function hasCpSection()
    {
        return true;
    }

    /**
     * Semi-smart logger.
     */
    public function logger($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    //----------------------
    // Entry table hooks
    //----------------------

    public function modifyEntryTableAttributes(&$attributes, $source)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        $attributes['author']      = Craft::t('Author');
        $attributes['email']       = Craft::t('Email');
    }

    public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
    {
        if (defined($attribute))
        {
            switch ($attribute)
            {
            case 'author':
                return $entry->author->name;
                break;

            case 'email':
                return $entry->email;
                break;
            }
        }
    }

    public function modifyEntrySortableAttributes(&$attributes)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        $attributes['authorId']    = Craft::t('Author');
        $attributes['email']       = Craft::t('Email');
    }

    //----------------------
    // User table hooks
    //----------------------

    public function modifyUserTableAttributes(&$attributes, $source)
    {
        $attributes['city'] = Craft::t('City');
    }

    public function modifyUserSortableAttributes(&$attributes)
    {
        $attributes['city'] = Craft::t('City');
    }

    //----------------------
    // Event functions
    //----------------------

    /**
     * Respond to entries.onSaveEntry event.
     *
     *  If entry is published:
     *
     *      Notify the admin.
     *
     *      If entry section is blog, letters, media, news or notices:
     *
     *          If content appears to contain offensive language:
     *
     *                  Notify the moderator.
     */
    private function _onSaveEntryEvent()
    {
        craft()->on('entries.onSaveEntry', function(Event $event) {
            $entry = $event->params['entry'];

            if ($entry->enabled)
            {
                craft()->mpAdmin->notifyAdminOfPublishedEntry($entry);

                $sections = array('blog', 'letters', 'media', 'news', 'notices');

                if (in_array($entry->section->handle, $sections))
                {
                    $text = craft()->mpAdmin->entryText($entry);

                    if (craft()->mpAdmin->containsTriggerWords($text))
                    {
                        craft()->mpAdmin->notifyModerator($entry);
                    }
                }
            }
        });
    }

    /**
     * Respond to users.onSaveUser event.
     *
     *  If user is a contributor:
     *
     *      If bio appears to contain offensive language:
     *
     *              Notify the moderator.
     */
    private function _onSaveUserEvent()
    {
        craft()->on('users.onSaveUser', function(Event $event) {
            $user = $event->params['user'];

            if ($user->isInGroup('contributor'))
            {
                $text = craft()->mpAdmin->userText($user);

                if (craft()->mpAdmin->containsTriggerWords($text))
                {
                    craft()->mpAdmin->notifyModerator($user);
                }
            }
        });
    }

    //----------------------
    // Private functions
    //----------------------

    /**
     * Inject Javascript into the Control Panel
     * to ensure that only one User Group is selected for a User.
     */
    private function _ensureOneUserGroup()
    {
        $js = <<<'JS'
var groups = $('form#userform input[type=checkbox][name="groups[]"]');

groups.click(function(e) {
    if (groups.filter(':checked').length > 1) {
        alert('Please select only one User Group.');

        return false;
    }
});
JS;
        craft()->templates->includeJs($js);
    }

    //---------
    // Settings
    //---------

    protected function defineSettings()
    {
        return array(
            'adminEmail'     => array(AttributeType::String, 'default' => ''),
            'forceLog'       => array(AttributeType::Bool,   'default' => false),
            'moderatorEmail' => array(AttributeType::String, 'default' => ''),
            'triggerWords'   => array(AttributeType::String, 'default' => ''),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('mpadmin/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //----------------------
    // Boilerplate functions
    //----------------------

    public function getName()
    {
        return 'Marin Post Admin';
    }

    public function getVersion()
    {
        return '1.3.1';
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
