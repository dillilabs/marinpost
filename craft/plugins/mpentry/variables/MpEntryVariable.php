<?php
namespace Craft;

class MpEntryVariable
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpentry');
    }

    public function impliedLocationIds($entryId)
    {
        return craft()->mpEntry->impliedLocationIds($entryId);
    }

    public function selectedLocationIds($entryId)
    {
        return craft()->mpEntry->selectedLocationIds($entryId);
    }

    public function locationIdsFrom($rootId = null)
    {
        return craft()->mpEntry->locationIdsFrom($rootId);
    }
}
