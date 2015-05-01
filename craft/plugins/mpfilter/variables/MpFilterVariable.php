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

    /**
     * Return parent Location ID
     *
     * @return int
     */
    public function parentLocationId($location)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Category);
        $criteria->relatedTo = array(
            'targetElement' => $location,
            'field' => 'geographicChildren'
        );

        return ($ids = $criteria->ids()) ? $ids[0] : null;
    }

    /**
     * Return children Location IDs
     *
     * @return string -- comma separated Location IDs
     */
    public function childLocationIds($location)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Category);
        $criteria->relatedTo = array(
            'sourceElement' => $location,
            'field' => 'geographicChildren'
        );

        return implode(',', $criteria->ids());
    }
}
