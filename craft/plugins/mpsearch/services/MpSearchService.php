<?php
namespace Craft;

class MpSearchService extends BaseApplicationComponent
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpsearch');
    }

    /**
     * Return array of Entry IDs for terms and optional section.
     *
     * If no section is specified then search all of:
     *
     *      blog
     *      letters
     *      media
     *      news
     *      notices
     */
    public function search($searchTerms, $section = false)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $this->_section($section);
        $criteria->search = $searchTerms;
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
            $criteria->section = $this->_section($section);
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
