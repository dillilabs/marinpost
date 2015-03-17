<?php
namespace Craft;

class MpEntryVariable
{
    private $settings;

    function __construct()
    {
        $this->settings = craft()->plugins->getPlugin('mpentry')->getSettings();
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
        //$this->_log(array('filters' => $filters, 'slice' => $slice));

        $locations = $this->_get($filters, 'locations');
        $topics = $this->_get($filters, 'topics');
        $authors = $this->_get($filters, 'authors');

        $section = $this->_get($filters, 'section');

        $offset = $this->_get($slice, 'offset', 0);
        $limit = $this->_get($slice, 'limit', $this->settings['defaultEntryLimit']);

        if (empty($locations) && empty($topics) && empty($authors))
        {
            $this->_log('No filters defined');

            $entries =  $this->_slice($offset, $limit, array(), $section);
        }
        else
        {
            $filteredEntryIds = $this->_filteredEntries($locations, $topics, $authors, $section);
            
            $this->_log(array('Filtered entries' => count($filteredEntryIds)));

            $entries =  $this->_slice($offset, $limit, $filteredEntryIds);
        }

        $this->_log(array('Sliced entries' => count($entries)));

        return $entries;
    }

    //------------------
    // Private functions
    //------------------

    private function _filteredEntries($locations, $topics, $authors, $section)
    {
        $entriesFilteredBy = array();

        $this->_log(array('locations' => $locations));

        if (!empty($locations))
        {
            $entriesFilteredBy['location'] = $this->_relatedEntries($locations, $section);
        }

        $this->_log(array('topics' => $topics));

        if (!empty($topics))
        {
            $entriesFilteredBy['topic'] = $this->_relatedEntries($topics, $section);
        }

        $this->_log(array('authors' => $authors));

        if (!empty($authors))
        {
            $entriesFilteredBy['author'] = $this->_authoredEntries($authors, $section);
        }

        foreach ($entriesFilteredBy as $key => $val)
        {
            $this->_log(array($key => count($val)));
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

        //$this->_log($criteria->attributes);

        return $criteria->ids();
    }

    private function _authoredEntries($authorIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;

        if ($authorIds) $criteria->authorId = $authorIds;

        //$this->_log($criteria->attributes);

        return $criteria->ids();
    }

    private function _slice($offset, $limit, $entryIds = array(), $section = false)
    {
        $this->_log("Offset:$offset, Limit:$limit, Section:$section, Entries:".count($entryIds));

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

    private function _log($mixed, $level = LogLevel::Info)
    {
        $message = is_array($mixed) ? json_encode($mixed) : $mixed;

        MpEntryPlugin::log($message, $level, $this->settings['forceLog']);
    }
}
