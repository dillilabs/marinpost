<?php

namespace Craft;

class MpSubscriptionOtherRecipientsTask extends BaseTask
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpsubscription');
    }

    public function getDescription()
    {
        return 'Weekly Update Email for Other Recipients';
    }

    public function getTotalSteps()
    {
        return 1;
    }

    public function runStep($step)
    {
        $this->plugin->logger("Running step $step for Other Recipients.");
        craft()->mpSubscription->sendWeeklyUpdateToOtherRecipients;

        return true;
    }
}
