<?php
namespace Craft;

class MpSearchVariable
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpsearch');
    }

    /**
     * Return array of Entry IDs for terms
     * and optional section.
     */
    public function search($searchTerms, $section = null)
    {
        // {% set query = craft.request.param('query') | replace('/^\\s+$/', '') %}
        // {% set searchTerms = query | split(' ') | filter | join(' OR ') %}
        // {% set section = craft.request.param('section') %}

        return craft()->mpSearch->search($searchTerms, $section);
    }
}
