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
     * Return array of Entries for optional filters:
     *
     *  (array) location ids
     *  (array) topic ids
     *  (array) author ids
     *  (string) section handle
     *
     * and slice by optional:
     *
     *  (integer) offset
     *  (integer) limit
     */
    public function entries($filters = array(), $slice = array())
    {
        //$this->plugin->logger(array('filters' => $filters, 'slice' => $slice));

        $locations = $this->_get($filters, 'locations');
        $topics = $this->_get($filters, 'topics');
        $authors = $this->_get($filters, 'authors');

        $section = $this->_get($filters, 'section');

        $offset = $this->_get($slice, 'offset', 0);
        $limit = $this->_get($slice, 'limit', $this->plugin->settings['defaultEntryLimit']);

        if (empty($locations) && empty($topics) && empty($authors))
        {
            $this->plugin->logger('No filters defined');

            $entries =  $this->_slice($offset, $limit, array(), $section);
        }
        else
        {
            $filteredEntryIds = $this->_filteredEntries($locations, $topics, $authors, $section);
            
            $this->plugin->logger(array('Filtered entries' => count($filteredEntryIds)));

            $entries = empty($filteredEntryIds) ? array() : $this->_slice($offset, $limit, $filteredEntryIds);
        }

        $this->plugin->logger(array('Sliced entries' => count($entries)));

        return $entries;
    }

    public function search($searchTerms, $section = null)
    {
        /*
        {% set query = craft.request.param('query') | replace('/^\\s+$/', '') %}
        {% set searchTerms = query | split(' ') | filter | join(' OR ') %}
        {% set section = craft.request.param('section') %}
        */
        return craft()->mpFilter->search($searchTerms, $section);
    }

    //------------------
    // Private functions
    //------------------

    private function _filteredEntries($locations, $topics, $authors, $section)
    {
        $entriesFilteredBy = array();

        $this->plugin->logger(array('locations' => $locations));

        if (!empty($locations))
        {
            $entriesFilteredBy['location'] = $this->_relatedEntries($locations, $section);
        }

        $this->plugin->logger(array('topics' => $topics));

        if (!empty($topics))
        {
            $entriesFilteredBy['topic'] = $this->_relatedEntries($topics, $section);
        }

        $this->plugin->logger(array('authors' => $authors));

        if (!empty($authors))
        {
            $entriesFilteredBy['author'] = $this->_authoredEntries($authors, $section);
        }

        foreach ($entriesFilteredBy as $key => $val)
        {
            $this->plugin->logger(array($key => count($val)));
        }

        switch(count($entriesFilteredBy))
        {
        case 0:
            $entries = array();
            break;
        case 1:
            $entries = array_pop($entriesFilteredBy);
            break;
        default:
            $entries = call_user_func_array('array_intersect', $entriesFilteredBy);
        }

        return $entries;
    }

    private function _relatedEntries($entryIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;

        if ($entryIds) $criteria->relatedTo = $entryIds;

        //$this->plugin->logger($criteria->attributes);

        return $criteria->ids();
    }

    private function _authoredEntries($authorIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;

        if ($authorIds) $criteria->authorId = $authorIds;

        //$this->plugin->logger($criteria->attributes);

        return $criteria->ids();
    }

    private function _slice($offset, $limit, $entryIds = array(), $section = false)
    {
        $this->plugin->logger("Offset:$offset, Limit:$limit, Section:$section, Entries:".count($entryIds));

        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if (!empty($entryIds)) $criteria->id = $entryIds;

        if ($section) $criteria->section = $section;

        $criteria->offset = $offset;

        $criteria->limit = $limit;

        return $criteria->find();
    }

    // ----------------
    // Helper functions
    // ----------------

    private function _get($array, $key, $default = false)
    {
        $value = $default;

        if (array_key_exists($key, $array))
        {
            $value = $array[$key];
            if (is_array($value)) {
                $value = array_filter($value);
            }
        }

        return $value;
    }
}
