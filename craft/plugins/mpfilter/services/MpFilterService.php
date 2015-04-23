<?php
namespace Craft;

class MpFilterService extends BaseApplicationComponent
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpfilter');
    }

    /**
     * Return array of Entry IDs for terms
     * and optional section.
     */
    public function search($searchTerms, $section = null)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->search = $searchTerms;
        if ($section) $criteria->section = $section;
        $criteria->order = 'score';
        $entryIds = $criteria->ids();
        $this->plugin->logger(array('entryIds' => $entryIds));

        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->search = $searchTerms;
        $criteria->order = 'score';
        $authorIds = $criteria->ids();
        $this->plugin->logger(array('authorIds' => $authorIds));

        if ($authorIds)
        {
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->authorId = $authorIds;
            if ($section) $criteria->section = $section;
            $criteria->order = 'score';
            $authorEntryIds = $criteria->ids();
            $this->plugin->logger(array('authorEntryIds' => $authorEntryIds));

            if ($authorEntryIds)
            {
                $entryIds = array_merge($entryIds, $authorEntryIds);
            }
        }

        $this->plugin->logger(array('entryIds' => $entryIds));
        return $entryIds;
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
            $this->plugin->logger(array('Filtered entries' => $filteredEntryIds));

            $entries = empty($filteredEntryIds) ? array() : $this->_slice($offset, $limit, $filteredEntryIds);
        }

        $this->plugin->logger(array('sliced entries' => $this->_entryIds($entries)));

        return $entries;
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
            // $entriesFilteredBy['location'] = $this->_relatedEntries($locations, $section);
            $entriesFilteredBy['location'] = $this->_locationEntries($locations, $section);
            $this->plugin->logger(array('entries filtered by location' => $entriesFilteredBy['location']));
        }

        $this->plugin->logger(array('topics' => $topics));

        if (!empty($topics))
        {
            $entriesFilteredBy['topic'] = $this->_relatedEntries($topics, $section);
            $this->plugin->logger(array('entries filtered by topic' => $entriesFilteredBy['topic']));
        }

        $this->plugin->logger(array('authors' => $authors));

        if (!empty($authors))
        {
            $entriesFilteredBy['author'] = $this->_authoredEntries($authors, $section);
            $this->plugin->logger(array('entries filtered by author' => $entriesFilteredBy['author']));
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

        $this->plugin->logger(array('filtered entries' => $entries));
        return $entries;
    }

    private function _relatedEntries($entryIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;
        if ($entryIds) $criteria->relatedTo = $entryIds;

        return $criteria->ids();
    }

    private function _authoredEntries($authorIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;
        if ($authorIds) $criteria->authorId = $authorIds;

        return $criteria->ids();
    }

    private function _slice($offset, $limit, $entryIds = array(), $section = false)
    {
        $this->plugin->logger("Offset:$offset, Limit:$limit, Section:$section, Entries:".count($entryIds));

        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if (!empty($entryIds))
        {
            $criteria->id = $entryIds;
            $criteria->fixedOrder = true;
        }

        if ($section) $criteria->section = $section;
        $criteria->offset = $offset;
        $criteria->limit = $limit;

        $this->plugin->logger(array('fixedOrder' => $criteria->fixedOrder, 'presliced entry ids' => $criteria->id));
        return $criteria->find();
    }

    /**
     * Return array of Entries related to Locations
     * and optionally a section.
     * NOTE in the case of childLocations we need to fetch full Entry records
     * to analyze the relative locality.
     */
    private function _locationEntries($locationIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;

        if ($locationIds)
        {
            $criteria->relatedTo = array(
                'targetElement' => $locationIds,
                'field' => 'primaryLocation',
            );
            $primaryIds= $criteria->ids();

            $criteria->relatedTo = array(
                'targetElement' => $locationIds,
                'field' => 'secondaryLocations',
            );
            $secondaryIds= $criteria->ids();

            $criteria->relatedTo = array(
                'targetElement' => $locationIds,
                'field' => 'childLocations',
            );
            $childEntries = $criteria->find();
            $childIds = $this->_localFirst($childEntries);

            $entryIds = array_unique(array_merge($primaryIds, $secondaryIds, $childIds));
        }
        else
        {
            $entryIds = $criteria->ids();
        }

        return $entryIds;
    }

    /**
     * Return array of Entries associated with one or more geographical "child" Locations
     * in order of "most local".
     * NOTE unlike the rest of the filtering process we need to operate on full Entry records.
     */
    private function _localFirst($entries)
    {
        $county = array();
        $region = array();
        $state = array();
        $country = array();
        $earth = array();

        foreach ($entries as $entry)
        {
            switch ($entry->primaryLocation->first()->id)
            {
            case $this->plugin->settings['countyId']:
                array_push($county, $entry);
                break;

            case $this->plugin->settings['regionId']:
                array_push($region, $entry);
                break;

            case $this->plugin->settings['stateId']:
                array_push($state, $entry);
                break;

            case $this->plugin->settings['countryId']:
                array_push($country, $entry);
                break;

            default:
                array_push($earth, $entry);
                break;
            }
        }

        $entries = array_merge($county, $region, $state, $country, $earth);
        return $this->_entryIds($entries);
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

    private function _entryIds($entries)
    {
        return array_map(function($entry) { return $entry->id; }, $entries);
    }
}
