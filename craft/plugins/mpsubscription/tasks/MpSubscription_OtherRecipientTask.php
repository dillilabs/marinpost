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
        return array('recipient' => AttributeType::Mixed);
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
        $settings  = $this->getSettings();
        $recipient = $settings->recipient;

        $this->plugin->logger("Running step $step for {$recipient->email}.");

        craft()->mpSubscription->sendEmailToOtherRecipient($recipient);

        return true;
    }
}
