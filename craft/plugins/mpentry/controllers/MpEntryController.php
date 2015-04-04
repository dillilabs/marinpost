<?php
namespace Craft;

class MpEntryController extends BaseController
{
    private $pluginSettings;
    private $currentUser;

    function __construct()
    {
        $this->pluginSettings = craft()->plugins->getPlugin('mpentry')->getSettings();
        $this->currentUser = craft()->userSession->isLoggedIn() ? craft()->userSession->user : null;
    }

    public function actionPublishEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();

        $this->_updateStatus($entry->id, BaseElementModel::ENABLED);

        $this->renderTemplate('account/entries/_update', array('success' => 'Content published.'));
    }

    public function actionUnpublishEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();

        $this->_ensureContributor($entry->author);

        $this->_updateStatus($entry->id, BaseElementModel::DISABLED);

        $this->renderTemplate('account/entries/_update', array('success' => 'Content unpublished.'));
    }

    public function actionDeleteEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();

        $this->_deleteEntry($entry->id);

        $this->renderTemplate('account/entries/_update', array('success' => 'Content deleted.'));
    }

    // ----------------
    // Helper functions
    // ----------------

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

    private function _ensureContributor($user)
    {
        if (!$user->isInGroup('contributor'))
        {
            $this->renderTemplate('account/entries/_update', array('error' => 'You are not authorized to un-publish content.'));
            return false;
        }

        return true;
    }

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

    private function _deleteEntry($entryId)
    {
        return craft()->entries->deleteEntryById($entryId);
    }

    private function _log($mixed, $level = LogLevel::Info)
    {
        $message = is_string($mixed) ? $mixed : json_encode($mixed);
        MpEntryPlugin::log($message, $level, $this->pluginSettings['forceLog']);
    }
}
