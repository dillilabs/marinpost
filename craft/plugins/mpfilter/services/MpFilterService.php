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
     *  (string) start date
     *  (string) end date
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

        $topics  = $this->_get($filters, 'topics');
        $authors = $this->_get($filters, 'authors');

        $startDate = $this->_get($filters, 'startDate');
        $endDate   = $this->_get($filters, 'endDate');

        $section = $this->_get($filters, 'section');

        $offset = $this->_get($slice, 'offset', 0);
        $limit  = $this->_get($slice, 'limit',  $this->plugin->settings['defaultEntryLimit']);

        if (empty($locations) && empty($topics) && empty($authors) && empty($startDate) && empty($endDate))
        {
            $this->plugin->logger('No filters defined');

            $entries = $this->_slice($offset, $limit, array(), $section);
        }
        else
        {
            $filteredEntryIds = $this->_filteredEntries($locations, $topics, $authors, $startDate, $endDate, $section);

            $this->plugin->logger(array('Filtered entries' => $filteredEntryIds));

            $entries = empty($filteredEntryIds) ? array() : $this->_slice($offset, $limit, $filteredEntryIds);
        }

        $this->plugin->logger(array('sliced entries' => $this->_entryIds($entries)));

        return $entries;
    }

    /**
     * Return array of Entry IDs for:
     *
     *   (datetime) start date
     *   (datetime) end date
     *
     * and optionally an array of:
     *
     *  (array of int) location ids
     *  (array of int) topic ids
     *  (array of int) author ids
     *  (boolean) letters
     *
     * Adapted from entries() to handle the subscription use case, where:
     *
     *   start- and end- date are required
     *   letters are included, but not filtered
     */
    public function entryIdsForSubscription($startDate, $endDate, $filter = array())
    {
        $this->plugin->logger(array('startDate' => $startDate, 'endDate' => $endDate, 'filter' => $filter));

        $locations = $this->_get($filter, 'locations', array());
        $topics    = $this->_get($filter, 'topics',    array());
        $authors   = $this->_get($filter, 'authors',   array());

        if (!empty($locations))
        {
            $locations = $this->_ensureProperlyScopedLocationFilter($locations);
        }

        $entryIds = $this->_filteredEntries($locations, $topics, $authors, $startDate, $endDate);

        if ($this->_get($filter, 'letters'))
        {
            $letterIds = $this->_datedEntries($startDate, $endDate, 'letters');
            $entryIds = array_merge($entryIds, $letterIds);
        }

        $this->plugin->logger(array('Entry ids for subscription' => $entryIds));

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
     * Return entires filtered by:
     *
     *   Location
     *   Topic
     *   Author
     *   StartDate
     *   EndDate
     *
     * and optional:
     *
     *   Section
     */
    private function _filteredEntries($locations, $topics, $authors, $startDate, $endDate, $section = false)
    {
        $entriesFilteredBy = array();

        $this->plugin->logger(array('locations' => $locations));

        if (!empty($locations))
        {
            $entriesFilteredBy['location'] = $this->_locationEntries($locations, $section);
            $this->plugin->logger(array('entries filtered by location' => $entriesFilteredBy['location']));
        }

        $this->plugin->logger(array('topics' => $topics));

        if (!empty($topics))
        {
            $entriesFilteredBy['topic'] = $this->_topicEntries($topics, $section);
            $this->plugin->logger(array('entries filtered by topic' => $entriesFilteredBy['topic']));
        }

        $this->plugin->logger(array('authors' => $authors));

        if (!empty($authors))
        {
            $entriesFilteredBy['author'] = $this->_authoredEntries($authors, $section);
            $this->plugin->logger(array('entries filtered by author' => $entriesFilteredBy['author']));
        }

        if (!empty($startDate))
        {
            $entriesFilteredBy['date'] = $this->_datedEntries($startDate, $endDate, $section);
            $this->plugin->logger(array('entries filtered by date' => $entriesFilteredBy['date']));
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
     * Return entries related to Author(s) and optional Section.
     */
    private function _authoredEntries($authorIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        $criteria->section = $this->_section($section);

        if ($authorIds) $criteria->authorId = $authorIds;

        return $criteria->ids();
    }

    private function _datedEntries($startDate = false, $endDate = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        $criteria->section = $this->_section($section);

        if ($startDate)
        {
            $criteria->after = $startDate;
        }

        if ($endDate)
        {
            $criteria->before = $endDate;
        }

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

        $criteria->section = $this->_section($section);
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

        $criteria->section = $this->_section($section);

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
     * Return entries related to Topic(s) and optional Section.
     */
    private function _topicEntries($topicIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        $criteria->section = $this->_section($section);

        if ($topicIds)
        {
            // First find entries directly associated via their Primary Topic
            $criteria->relatedTo = array(
                'targetElement' => $topicIds,
                'field' => 'primaryTopic',
            );
            $primary = $criteria->ids();

            // Next find entries directly associated via a Secondary Topic
            $criteria->relatedTo = array(
                'targetElement' => $topicIds,
                'field' => 'secondaryTopics',
            );
            $secondary = $criteria->ids();

            $this->plugin->logger(array('primary' => $primary));
            $this->plugin->logger(array('secondary' => $secondary));

            $entryIds = array_unique(array_merge($primary, $secondary));
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

    /**
     * Confine filter to sections with user-created content.
     */
    private function _section($section = false)
    {
        return $section ? $section : array('blog', 'letters', 'media', 'news', 'notices');
    }

}
