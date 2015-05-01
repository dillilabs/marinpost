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
        $this->plugin->logger(array('filters' => $filters, 'slice' => $slice));

        $locations = $this->_get($filters, 'locations');
        $locations = $this->_ensureProperlyScopedLocationFilter($locations);

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

    //------------------
    // Private functions
    //------------------

    /**
     * Ensure that Location filter consists of either:
     *
     *      a single Location (of any type)
     *
     *  or, coerce to:
     *
     *     a single "parent" Location
     *
     * or else:
     *
     *      multiple, non-parent Locations
     */
    private function _ensureProperlyScopedLocationFilter($locations)
    {
        if (count($locations) == 1)
        {
            return $locations;
        }

        $parentLocations = array('country', 'state', 'region', 'county');

        foreach ($parentLocations as $parentLocation)
        {
            $parentLocationId = $this->plugin->settings[$parentLocation.'Id'];

            if (in_array($parentLocationId, $locations))
            {
                $this->plugin->logger(array('Warning' => 'Found more than a single parent Location'), LogLevel::Warning);

                return array($parentLocationId);
            }
        }

        // multiple, non-parent Locations
        return $locations;
    }

    /**
     * Return entires filtered by Location(s), Topic(s), Author(s) and optional Section.
     */
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

    /**
     * Return entries related to Topic(s) and optional Section.
     */
    private function _relatedEntries($entryIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;
        if ($entryIds) $criteria->relatedTo = $entryIds;

        return $criteria->ids();
    }

    /**
     * Return entries related to Author(s) and optional Section.
     */
    private function _authoredEntries($authorIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;
        if ($authorIds) $criteria->authorId = $authorIds;

        return $criteria->ids();
    }

    /**
     * Return portion of entries sliced by offset and limit.
     */
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
     * Return array of Entries related to one or more Locations,
     * and optionally a Section.
     *
     * Locations will be either:
     *
     *      a single Location, representing either:
     *
     *          a parent Location -- county, region, state or country
     *
     *      or:
     *
     *          a non-parent Location
     *
     *  or:
     *
     *      multiple, non-parent Locations
     */
    private function _locationEntries($locationIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        if ($section) $criteria->section = $section;

        if ($locationIds)
        {
            // First find entries directly associated via their Primary Location
            $criteria->relatedTo = array(
                'targetElement' => $locationIds,
                'field' => 'primaryLocation',
            );
            $primary = $criteria->ids();

            // Next find entries directly associated via a Secondary Location
            $criteria->relatedTo = array(
                'targetElement' => $locationIds,
                'field' => 'secondaryLocations',
            );
            $secondary = $criteria->ids();

            // Then find entries indirectly associated via a parent/child Location relationship
            switch ($locationIds[0])
            {
            case $this->plugin->settings['countryId']:
            case $this->plugin->settings['stateId']:
            case $this->plugin->settings['regionId']:
            case $this->plugin->settings['countyId']:
                // Filtering by one of the "parent" Locations
                // so find entries associated with any of its "children" Locations

                $childrenLocations = $this->_childrenLocationIds($locationIds[0]);
                $this->plugin->logger(array('children' => $childrenLocations));

                // First find entries associated via their Primary Location to the parent of the current Location(s)
                $criteria->relatedTo = array(
                    'targetElement' => $childrenLocations,
                    'field' => 'primaryLocation',
                );
                $tertiary = $criteria->ids();

                // Then find entries associated via a Secondary Location to the parent of the current Location(s)
                $criteria->relatedTo = array(
                    'targetElement' => $childrenLocations,
                    'field' => 'secondaryLocations',
                );
                $quaternary = $criteria->ids();
                break;

            default:
                // Filter by one or more Locations of the same parent, namely, Marin county
                // So find entries associated to Marin county

                $parentLocation = $this->plugin->settings['countyId'];

                // First find entries associated with Marin County via their Primary Location
                $criteria->relatedTo = array(
                    'targetElement' => $parentLocation,
                    'field' => 'primaryLocation',
                );
                $tertiary = $criteria->ids();

                // Then find entries associated with Marin County via a Secondary Location
                $criteria->relatedTo = array(
                    'targetElement' => $parentLocation,
                    'field' => 'secondaryLocations',
                );
                $quaternary = $criteria->ids();
            }

            $this->plugin->logger(array('primary' => $primary));
            $this->plugin->logger(array('secondary' => $secondary));
            $this->plugin->logger(array('tertiary' => $tertiary));
            $this->plugin->logger(array('quaternary' => $quaternary));

            $entryIds = array_unique(array_merge($primary, $secondary, $tertiary, $quaternary));
        }
        else
        {
            $entryIds = $criteria->ids();
        }

        return $entryIds;
    }

    /**
     * Return array of Locations which are children of the given Location.
     */
    private function _childrenLocationIds($locationId)
    {
        switch ($locationId)
        {
        case $this->plugin->settings['countryId']:
            $selfAndParentIds = array(
                $this->plugin->settings['countryId'],
            );
            break;

        case $this->plugin->settings['stateId']:
            $selfAndParentIds = array(
                $this->plugin->settings['stateId'],
                $this->plugin->settings['countryId'],
            );
            break;

        case $this->plugin->settings['regionId']:
            $selfAndParentIds = array(
                $this->plugin->settings['regionId'],
                $this->plugin->settings['stateId'],
                $this->plugin->settings['countryId'],
            );
            break;

        case $this->plugin->settings['countyId']:
            $selfAndParentIds = array(
                $this->plugin->settings['countyId'],
                $this->plugin->settings['regionId'],
                $this->plugin->settings['stateId'],
                $this->plugin->settings['countryId'],
            );
            break;

        default:
            $selfAndParentIds = array();
        }

        $this->plugin->logger(array('self and parents', $selfAndParentIds));

        $criteria = craft()->elements->getCriteria(ElementType::Category);
        $criteria->group('locations');

        return array_diff($criteria->ids(), $selfAndParentIds);
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
