<?php

namespace Craft;

class MpSubscriptionTask extends BaseTask
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpsubscription');
    }

    protected function defineSettings()
    {
        return array('user' => AttributeType::Mixed);
    }

    public function getDescription()
    {
        return 'Subscription Email Alert';
    }

    public function getTotalSteps()
    {
        return 1; 
    }

    public function runStep($step)
    {
        $settings = $this->getSettings();
        $user     = $settings->user;

        $this->plugin->logger("Running step $step for $user.");
        craft()->mpSubscription->sendEmailToUser($user);

        return true;
    }
}
