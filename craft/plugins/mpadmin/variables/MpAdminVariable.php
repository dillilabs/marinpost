<?php
namespace Craft;

class MpAdminVariable
{

    /**
     * Return true if Minimee is enabled.
     */
    public function minimeeEnabled()
    {
        $minimee = craft()->plugins->getPlugin('minimee');

        return $minimee && $minimee->isEnabled && $minimee->settings->enabled;
    }
}
