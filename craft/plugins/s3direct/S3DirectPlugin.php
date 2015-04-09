<?php
namespace Craft;

class S3DirectPlugin extends BasePlugin
{
    //
    // Settings
    //

    protected function defineSettings()
    {
        return array(
            'imageTransform' => array(AttributeType::String, 'default' => 'list', 'required' => true),
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
        return '0.0.3';
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
