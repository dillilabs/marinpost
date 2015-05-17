<?php
namespace Craft;

class MpEntryService extends BaseApplicationComponent
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpentry');
    }

    /**
     * Validate (disabled) entry, add errors and return false if invalid.
     */
    public function isValidEntry($entry)
    {
        if (craft()->content->validateContent($entry))
        {
            return true;
        }
        else
        {
            $entry->addErrors($entry->getContent()->getErrors());
            return false;
        }
    }

    /**
     * Return true if entry has already been published.
     */
    public function isPublishedEntry($entry)
    {
        if ($entry->id)
        {
            $originalEntry = craft()->entries->getEntryById($entry->id);

            return $originalEntry->status == 'live';
        }
        else
        {
            return false;
        }
    }

    /**
     * Update the status of an Entry.
     */
    public function updateStatus($entryId, $status)
    {
        $elementIds = array($entryId);
        $locale = 'en_us';
        $criteria = craft()->elements->getCriteria(
            ElementType::Entry,
            array('id' => $elementIds, 'locale' => $locale)
        );

        // The remainder of this function is borrowed from
        // SetStatusElementAction::performAction()

        // Figure out which element IDs we need to update
        if ($status == BaseElementModel::ENABLED)
        {
            $sqlNewStatus = '1';
        }
        else
        {
            $sqlNewStatus = '0';
        }

        // Update their statuses
        craft()->db->createCommand()->update(
            'elements',
            array('enabled' => $sqlNewStatus),
            array('in', 'id', $elementIds)
        );

        if ($status == BaseElementModel::ENABLED)
        {
            // Enable their locale as well
            craft()->db->createCommand()->update(
                'elements_i18n',
                array('enabled' => $sqlNewStatus),
                array('and', array('in', 'elementId', $elementIds), 'locale = :locale'),
                array(':locale' => $criteria->locale)
            );
        }

        // Clear their template caches
        craft()->templateCache->deleteCachesByElementId($elementIds);

        // Fire an 'onSetStatus' event
        $event = new Event($this, array(
            'criteria'   => $criteria,
            'elementIds' => $elementIds,
            'status'     => $status,
        ));

        $this->raiseEvent('onSetStatus', $event);
    }

    /**
     * Set post date and URL slug of never-before published Entry.
     */
    public function setPostDateAndSlug($entryId)
    {
        $entryRecord = EntryRecord::model()->findById($entryId);
        if (!$entryRecord->postDate)
        {
            $entryRecord->saveAttributes(array('postDate' => DateTimeHelper::currentTimeForDb()));
        }

        $entry = craft()->entries->getEntryById($entryId);
        craft()->elements->updateElementSlugAndUri($entry);
    }

    /**
     * Archive an entry...in lieu of actually deleting it.
     */
    public function archiveEntry($entry)
    {
        $record = ElementRecord::model()->findByAttributes(array(
            'id'   => $entry->id,
            'type' => $entry->getElementType()
        ));
        $record->archived = true;

        return $record->save(false);
    }

    /**
     * Un-archive an entry.
     */
    public function unarchiveEntry($entry)
    {
        $record = ElementRecord::model()->findByAttributes(array(
            'id'   => $entry->id,
            'type' => $entry->getElementType()
        ));
        $record->archived = false;

        return $record->save(false);
    }

    /**
     * Use the entry's primary Location to populate implicit child Locations.
     * And update the search index.
     */
    public function synchronizeChildLocations($entry)
    {
        $locationIds = $this->_impliedLocationIds($entry);

        $field = craft()->fields->getFieldByHandle('childLocations');
        $savedRelations = craft()->relations->saveRelations($field, $entry, $locationIds);

        $entry->setContentFromPost(array(
            'childLocations' => $locationIds
        ));
        $savedElement = craft()->elements->saveElement($entry, false);

        $this->_updateSearchIndex($entry);

        $this->plugin->logger("synchronizeChildLocations() entry=$entry [{$entry->id}] saved relations=$savedRelations, saved element=$savedElement, locationIds=".json_encode($locationIds));
    }

    //------------------
    // Private functions
    //------------------

    /**
     * Return array of Location IDs from Locations which are geographical "children" of the Primary Location
     */
    private function _impliedLocationIds($entry)
    {
        $primaryLocationId = $entry->primaryLocation->first()->id;
        $impliedLocationIds = $this->_locationIdsFrom($primaryLocationId);
        $selectedLocationIds = $this->_selectedLocationIds($entry);

        return array_values(array_diff(array_unique($impliedLocationIds), $selectedLocationIds));
    }

    /**
     * Return array of entry's Primary and Secondary Location IDs
     */
    private function _selectedLocationIds($entry)
    {
        $locations = $this->_selectedLocations($entry);
        $ids = array_map(function($location) { return $location->id; }, $locations);

        return $ids;
    }

    /**
     * Return array of entry's Primary and Secondary Locations
     */
    private function _selectedLocations($entry)
    {
        $selectedLocations = array();

        $primaryLocation = $entry->primaryLocation->first();
        array_push($selectedLocations, $primaryLocation);

        foreach ($entry->secondaryLocations as $location)
        {
            array_push($selectedLocations, $location);
        }

        return array_unique($selectedLocations);
    }

    /**
     * Return array of geographic "child" Location IDs starting from parent Location.
     */
    private function _locationIdsFrom($rootId)
    {
        $locations = $this->_locationsFrom($rootId);

        return $this->_valuesForKey($locations, 'id');
    }

    /**
     * Return array of geographic "child" Locations starting from parent Location.
     */
    private function _locationsFrom($rootId)
    {
        $nodes = array();
        $root = craft()->categories->getCategoryById($rootId);

        if ($root)
        {
            $node = array('id' => $root->id, 'self' => $root);

            if ($children = $this->_childLocationsOf($root))
            {
                $node['children'] = $children;
            }

            $nodes[$root->id] = $node;
        }

        return $nodes;
    }

    /**
     * Return array of geographic "child" of Locations of parent Location:
     *
     *      array(
     *          array('id' => 1, 'self' => object1, 'children' => array(...),
     *          array('id' => 2, 'self' => object2, 'children' => array(...),
     *          ...
     *      )
     */
    private function _childLocationsOf($parent)
    {
        $nodes = array();

        foreach($parent->geographicChildren as $child)
        {
            $node = array('id' => $child->id, 'self' => $child);

            // recurse
            if ($children = $this->_childLocationsOf($child))
            {
                $node['children'] = $children;
            }

            $nodes[$child->id] = $node;
        }

        return $nodes;
    }

    /**
     * Return an array of values drawn from a nested, associative array
     * but only those of a specific key.
     */
    private function _valuesForKey(array $input, $key)
    {
        $values = array();

        array_walk_recursive($input, function($v, $k) use($key, &$values) {
            if ($k == $key)
            {
                array_push($values, $v);
            }
        });

        return $values;
    }

    /**
     * Update Craft search index for entry.
     *
     * Borrowed from SearchIndexTool#performAction
     */
    private function _updateSearchIndex($element)
    {
        $elementType = craft()->elements->getElementType($element->elementType);

        if ($elementType->isLocalized())
        {
            $localeIds = craft()->i18n->getSiteLocaleIds();
        }
        else
        {
            $localeIds = array(craft()->i18n->getPrimarySiteLocaleId());
        }

        $criteria = craft()->elements->getCriteria($element->elementType, array(
            'id'            => array($element->id),
            'status'        => null,
            'localeEnabled' => null,
        ));

        foreach ($localeIds as $localeId)
        {
            $criteria->locale = $localeId;
            $element = $criteria->first();

            if ($element)
            {
                craft()->search->indexElementAttributes($element);

                if ($elementType->hasContent())
                {
                    $fieldLayout = $element->getFieldLayout();
                    $keywords = array();

                    foreach ($fieldLayout->getFields() as $fieldLayoutField)
                    {
                        $field = $fieldLayoutField->getField();

                        if ($field)
                        {
                            $fieldType = $field->getFieldType();

                            if ($fieldType)
                            {
                                $fieldType->element = $element;

                                $handle = $field->handle;

                                // Set the keywords for the content's locale
                                $fieldSearchKeywords = $fieldType->getSearchKeywords($element->getFieldValue($handle));
                                $keywords[$field->id] = $fieldSearchKeywords;
                            }
                        }
                    }

                    craft()->search->indexElementFields($element->id, $localeId, $keywords);
                }
            }
        }
    }
}
