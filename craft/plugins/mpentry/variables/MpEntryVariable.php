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
     * Convert mono-cased string to ucfirst.
     */
    function titleCase($title)
    {
        return $this->_monocase($title) ? ucfirst(strtolower($title)) : $title;
    }

    /**
     * Convert mono-cased string to ucwords.
     */
    function nameCase($name)
    {
        return $this->_monocase($name) ? ucwords(strtolower($name)) : $name;
    }

    /**
     * Recreate child locations of entry.
     */
    function synchronizeChildLocations($entry)
    {
        return craft()->mpEntry->synchronizeChildLocations($entry);
    }

    //--------------------
    // Private functions
    //--------------------

    function _monocase($string)
    {
        $monocase = array(
            strtolower($string),
            strtoupper($string)
        );

        return in_array($string, $monocase);
    }
}
