<?php
namespace Craft;

class MpUserVariable
{
    private $plugin;
    private $contactFormPlugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpuser');
        $this->contactFormPlugin = craft()->plugins->getPlugin('contactform');
    }

    public function registerFormHoneypotField()
    {
        return $this->plugin->settings['honeypotField'];
    }

    public function contactFormHoneypotField()
    {
        return $this->contactFormPlugin->settings['honeypotField'];
    }
}
