<?php
namespace Craft;

class MpFilterPlugin extends BasePlugin
{

    /**
     * Semi-smart logger.
     */
    public function logger($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    //---------
    // Settings
    //---------

    protected function defineSettings()
    {
        return array(
            'countyId'  => array(AttributeType::String, 'default' => 755),
            'regionId'  => array(AttributeType::String, 'default' => 39),
            'stateId'   => array(AttributeType::String, 'default' => 40),
            'countryId' => array(AttributeType::String, 'default' => 41),

            'defaultEntryLimit' => array(AttributeType::String, 'default' => 10),

            'forceLog' => array(AttributeType::Bool, 'default' => false),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('mpfilter/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //----------------------
    // Boilerplate functions
    //----------------------

    public function getName()
    {
        return 'Marin Post Filter';
    }

    public function getVersion()
    {
        return '1.3.0';
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
