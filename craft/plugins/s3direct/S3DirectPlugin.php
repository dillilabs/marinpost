<?php
namespace Craft;

class S3DirectPlugin extends BasePlugin
{
    /**
     * Semi-smart logger.
     */
    public function logger($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    //
    // Settings
    //

    protected function defineSettings()
    {
        return array(
            'defaultImageTransform' => array(AttributeType::String, 'default' => 'list', 'required' => true),
            'forceLog' => array(AttributeType::Bool, 'default' => false),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('s3direct/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //
    // Boilerplate
    //

    public function getName()
    {
        return 'S3 Direct';
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
