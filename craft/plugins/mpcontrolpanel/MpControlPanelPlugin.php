<?php
namespace Craft;

class MpControlPanelPlugin extends BasePlugin
{
    /**
     * Initialization:
     *
     *  Inject Javascript into the Control Panel to:
     *
     *    Ensure only a single User group is selected per User 
     *
     *    Load JS resources for Redactor Plugins
     */
    public function init()
    {
        parent::init();

        $this->settings = $this->getSettings();

        if (craft()->request->isCpRequest())
        {
            $this->_ensureOneUserGroup();
            $this->_loadRedactorPluginResources();
        }
    }

    //----------------------
    // Hook functions
    //----------------------

    public function modifyEntryTableAttributes(&$attributes, $source)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        $attributes['author'] = Craft::t('Author');
    }

    public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
    {
        if (defined($attribute) && $attribute == 'author')
        {
            return $entry->author->name;
        }
    }

    public function modifyEntrySortableAttributes(&$attributes)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        // $attributes['author'] = Craft::t('Author');
    }

    //----------------------
    // Private functions
    //----------------------

    /**
     * Inject Javascript into the Control Panel to ensure that
     * only one User Group is selected for a User.
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

    /**
     * Load JS resources for Redactor Plugins
     */
    private function _loadRedactorPluginResources()
    {
        $plugins = array('fontfamily', 'fontsize', 'fontcolor');
        foreach ($plugins as $plugin)
        {
            craft()->templates->includeJsResource("lib/redactor/plugins/{$plugin}.js");
        }
    }

    // ----------------
    // Helper functions
    // ----------------

    private function _log($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    //---------
    // Settings
    //---------

    protected function defineSettings()
    {
        return array(
            'forceLog' => array(AttributeType::Bool, 'default' => false),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('mpcontrolpanel/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //----------------------
    // Boilerplate functions
    //----------------------

    public function getName()
    {
        return 'Marin Post Control Panel';
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
