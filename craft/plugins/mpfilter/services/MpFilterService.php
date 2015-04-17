<?php
namespace Craft;

class MpFilterService extends BaseApplicationComponent
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpfilter');
    }

    public function search($searchTerms, $section = null)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->search = $searchTerms;
        if ($section) $criteria->section = $section;
        $criteria->order = 'score';
        $entryIds = $criteria->ids();
        $this->plugin->logger('entryIds='.json_encode($entryIds));

        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->search = $searchTerms;
        $criteria->order = 'score';
        $authorIds = $criteria->ids();
        $this->plugin->logger('authorIds='.json_encode($authorIds));

        if ($authorIds)
        {
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->authorId = $authorIds;
            if ($section) $criteria->section = $section;
            $criteria->order = 'score';
            $authorEntryIds = $criteria->ids();
            $this->plugin->logger('authorEntryIds='.json_encode($authorEntryIds));

            if ($authorEntryIds)
            {
                $entryIds = array_merge($entryIds, $authorEntryIds);
            }
        }

        return $entryIds;
    }
}
