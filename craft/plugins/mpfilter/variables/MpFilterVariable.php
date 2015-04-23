<?php
namespace Craft;

class MpFilterVariable
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpfilter');
    }

    /**
     * Return array of Entries optionally filtered
     * and sliced.
     */
    public function entries($filters = array(), $slice = array())
    {
        return craft()->mpFilter->entries($filters, $slice);
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

        return craft()->mpFilter->search($searchTerms, $section);
    }
}
