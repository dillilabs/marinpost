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
     * Populate entry's implicit child Locations
     * from it's explicit primary and secondary Locations.
     * And update the search index.
     */
    public function synchronizeChildLocations($entry)
    {
            $locationIds = $this->_impliedLocationIds($entry->id);

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

    private function _impliedLocationIds($entryId)
    {
        $selectedLocationIds = $this->_selectedLocationIds($entryId);

        $impliedLocationIds = array();

        foreach ($selectedLocationIds as $selectedLocationId)
        {
            $childLocationIds = $this->_locationIdsFrom($selectedLocationId);

            $impliedLocationIds = array_merge($impliedLocationIds, $childLocationIds);
        }

        $impliedLocationIds = array_unique($impliedLocationIds);

        return array_values(array_diff(array_unique($impliedLocationIds), $selectedLocationIds));
    }

    private function _selectedLocationIds($entryId)
    {
        $entry = craft()->entries->getEntryById($entryId);

        $locations = $this->_selectedLocations($entry);

        $ids = array_map(function($location) { return $location->id; }, $locations);

        sort($ids);

        return $ids;
    }

    private function _selectedLocations($entry)
    {
        $selectedLocations = array();

        array_push($selectedLocations, $entry->primaryLocation->first());

        foreach ($entry->secondaryLocations as $location)
        {
            array_push($selectedLocations, $location);
        }

        return array_unique($selectedLocations);
    }

    private function _locationIdsFrom($rootId)
    {
        $locations = $this->_locationsFrom($rootId);

        $ids = $this->_valuesForKey($locations, 'id');

        if (is_array($ids))
        {
            sort($ids);
        }
        else
        {
            $ids = array($ids);
        }

        return $ids;
    }

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

    private function _valuesForKey(array $input, $key)
    {
        $values = array();

        array_walk_recursive($input, function($v, $k) use($key, &$values) {
            if ($k == $key)
            {
                array_push($values, $v);
            }
        });

        return count($values) > 1 ? $values : array_pop($values);
    }

    /**
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
