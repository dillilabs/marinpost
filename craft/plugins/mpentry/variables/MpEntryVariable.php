<?php
namespace Craft;

class MpEntryVariable
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpentry');
    }

    function synchronizeChildLocations($entry)
    {
        return craft()->mpEntry->synchronizeChildLocations($entry);
    }
}
