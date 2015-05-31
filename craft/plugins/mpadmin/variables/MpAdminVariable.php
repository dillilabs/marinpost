<?php
namespace Craft;

class MpAdminVariable
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpadmin');
    }

    /**
     * Email admin on server error.
     */
    public function notifyAdminOfServerError($errorMessage)
    {
        craft()->mpAdmin->notifyAdminOfServerError($errorMessage);
    }

    /**
     * Return true if Minimee is enabled.
     */
    public function minimeeEnabled()
    {
        $minimee = craft()->plugins->getPlugin('minimee');

        return $minimee && $minimee->isEnabled && $minimee->settings->enabled;
    }
}
