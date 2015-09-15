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

    /**
     * Return true if User is an admin assistant.
     */
    public function isAdmin($user)
    {
        return craft()->mpAdmin->isAdminOrAdminAssistant();
    }
}
