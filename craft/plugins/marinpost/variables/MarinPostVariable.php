<?php
namespace Craft;

class MarinPostVariable
{
    const DEFAULT_LIMIT = 10;

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
        $this->_log(array('filters' => $filters, 'slice' => $slice));

        $filteredEntries = $this->_filtered_entries($filters);

        $offset = $this->_get($slice, 'offset', 0);
        $limit = $this->_get($slice, 'limit', self::DEFAULT_LIMIT);

        $slicedEntries = array_slice($filteredEntries, $offset, $limit);

        return $slicedEntries;
    }

    //------------------
    // Private functions
    //------------------

    private function _filtered_entries($filters)
    {
        $section = $this->_get($filters, 'section');

        $locations = $this->_get($filters, 'locations');
        $locationEntries = $this->_relatedEntries($locations, $section);

        $topics = $this->_get($filters, 'topics');
        $topicEntries = $this->_relatedEntries($topics, $section);

        $authors = $this->_get($filters, 'authors');
        $authorEntries = $this->_authoredEntries($authors, $section);

        $filteredEntryArrays = array_filter(
            array(
                $locationEntries,
                $topicEntries,
                $authorEntries
            )
        );

        $this->_log(array(
            'locations entries' => count($locationEntries),
            'topics entries' => count($topicEntries),
            'authors entries' => count($authorEntries),
            'filtered entry arrays' => count($filteredEntryArrays),
        ));

        switch(count($filteredEntryArrays))
        {
        case 0:
            $entries = craft()->elements->findElements();
            break;
        case 1:
            $entries = array_pop($filteredEntryArrays);
            break;
        default:
            $entries = call_user_func_array('array_intersect', $filteredEntryArrays);
        }

        return $entries;
    }

    private function _relatedEntries($entryIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;

        if ($entryIds) $criteria->relatedTo = $entryIds;

        //$this->_log($criteria->attributes);

        return $criteria->find();
    }

    private function _authoredEntries($authorIds = false, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);

        if ($section) $criteria->section = $section;

        if ($authorIds) $criteria->authorId = $authorIds;

        //$this->_log($criteria->attributes);

        return $criteria->find();
    }

    // ----------------
    // Helper functions
    // ----------------

    private function _get($array, $key, $default = false)
    {
        if (array_key_exists($key, $array))
        {
            return $array[$key];
        }
        else
        {
            return $default;
        }
    }

    private function _log($mixed, $level = LogLevel::Warning)
    {
        MarinPostPlugin::log(print_r($mixed, true), $level);
    }
}
