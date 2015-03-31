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

    public function actionUnpublishEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();

        $this->_ensureContributor($entry->author);

        $this->_disableElement($entry->id);

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

    /**
     * Contributor only
     */
    private function _ensureContributor($user)
    {
        if ($user->isInGroup('guest'))
        {
            $this->renderTemplate('account/entries/_update', array('error' => 'You are not authorized to un-publish content.'));
            return false;
        }

        return true;
    }

    /**
	 * Borrowed from SetStatusElementAction::performAction()
     */
    private function _disableElement($entryId)
    {
        $elementIds = array($entryId);

		craft()->db->createCommand()->update(
			'elements',
			array('enabled' => 0),
			array('in', 'id', $elementIds)
		);

		craft()->templateCache->deleteCachesByElementId($elementIds);
    }

    private function _deleteEntry($entryId)
    {
        return craft()->entries->deleteEntryById($entryId);
    }

    private function _log($mixed, $level = LogLevel::Info)
    {
        $message = is_array($mixed) ? json_encode($mixed) : $mixed;
        MpEntryPlugin::log($message, $level, $this->pluginSettings['forceLog']);
    }
}
