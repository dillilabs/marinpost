<?php

namespace Craft;

class MpSubscription_OtherRecipientTask extends BaseTask
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpsubscription');
    }

    protected function defineSettings()
    {
        return array('emailAddress' => AttributeType::String);
    }

    public function getDescription()
    {
        return 'Weekly Update Email to other, non-User recipient';
    }

    public function getTotalSteps()
    {
        return 1;
    }

    public function runStep($step)
    {
        $settings     = $this->getSettings();
        $emailAddress = $settings->emailAddress;

        $this->plugin->logger("Running step $step for $emailAddress.");
        craft()->mpSubscription->sendEmailToAddress($emailAddress);

        return true;
    }
}
