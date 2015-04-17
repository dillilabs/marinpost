<?php
namespace Craft;

class MpEntryController extends BaseController
{
    private $plugin;
    private $currentUser;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpentry');
        $this->currentUser = craft()->userSession->isLoggedIn() ? craft()->userSession->user : null;
    }

    /**
     * Publish an entry directly by setting the status.
     */
    public function actionPublishEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();

        $this->_updateStatus($entry->id, BaseElementModel::ENABLED);

        // Updating the status of a never-before published entry
        // does not update either the postDate or the URI slug
        // so we must do it manually.
        $this->_setPostDateAndSlug($entry->id);

        $this->renderTemplate('account/entries/_update', array('success' => 'Content published.'));
    }

    /**
     * Unpublish an entry directly by setting the status.
     */
    public function actionUnpublishEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();

        $this->_ensureContributor($entry->author);

        $this->_updateStatus($entry->id, BaseElementModel::DISABLED);

        $this->renderTemplate('account/entries/_update', array('success' => 'Content unpublished.'));
    }

    /**
     * Just that.
     */
    public function actionDeleteEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();

        $this->_deleteEntry($entry->id);

        $this->plugin->logger("[{$this->currentUser}] ({$this->currentUser->id}) deleted [{$entry}] ({$entry->id}) from {$entry->section}", LogLevel::Warning);

        $this->renderTemplate('account/entries/_update', array('success' => 'Content deleted.'));
    }

    // ----------------
    // Helper functions
    // ----------------

    /**
     * Fetch the entry and ensure authorship.
     */
    private function _getEntry()
    {
        $entryId = craft()->request->getParam('entryId');

        if (!$entryId)
        {
            $this->renderTemplate('account/entries/_update', array('error' => 'Content ID is required.'));
        }

        $entry = craft()->entries->getEntryById($entryId);

        if (!$entry)
        {
            $this->renderTemplate('account/entries/_update', array('error' => 'Content not found.'));
        }

        if ($entry->author->id != $this->currentUser->id)
        {
            $this->renderTemplate('account/entries/_update', array('error' => 'You are not authorized to update this content.'));
        }

        return $entry;
    }

    /**
     * Some functionality is only available to contributors.
     */
    private function _ensureContributor($user)
    {
        if (!$user->isInGroup('contributor'))
        {
            $this->renderTemplate('account/entries/_update', array('error' => 'You are not authorized to un-publish content.'));
            return false;
        }

        return true;
    }

    /**
     * Update the status already.
     */
    private function _updateStatus($entryId, $status)
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

    private function _setPostDateAndSlug($entryId)
    {
        $entryRecord = EntryRecord::model()->findById($entryId);

        if (!$entryRecord->postDate)
        {
            $entryRecord->saveAttributes(array('postDate' => DateTimeHelper::currentTimeForDb()));
        }

        $entry = craft()->entries->getEntryById($entryId);

        craft()->elements->updateElementSlugAndUri($entry);
    }

    private function _deleteEntry($entryId)
    {
        return craft()->entries->deleteEntryById($entryId);
    }
}
