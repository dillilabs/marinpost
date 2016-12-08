<?php
namespace Craft;

class MpEntryVariable
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpentry');
    }

    /**
     * Convert mono-cased string to ucfirst
     * and always start with a capital.
     */
    public function titleCase($title)
    {
        return $this->_monocase($title) ? ucfirst(strtolower($title)) : ucfirst($title);
    }

    /**
     * Convert mono-cased string to ucwords
     * and always start with a capital.
     */
    public function nameCase($name)
    {
        return $this->_monocase($name) ? ucwords(strtolower($name)) : ucfirst($name);
    }

    /**
     * Recreate child locations of entry.
     */
    public function synchronizeChildLocations($entry)
    {
        return craft()->mpEntry->synchronizeChildLocations($entry);
    }

    /**
     * Post entry pageview to Google Analytics via Measurement Protocol.
     */
    public function postToGoogleAnalytics($entry)
    {
        return craft()->mpEntry->postToGoogleAnalytics($entry);
    }

    //--------------------
    // Private functions
    //--------------------

    private function _monocase($string)
    {
        $monocase = array(
            strtolower($string),
            strtoupper($string)
        );

        return in_array($string, $monocase);
    }
}
