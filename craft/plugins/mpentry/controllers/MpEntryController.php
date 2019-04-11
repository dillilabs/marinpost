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
     * Front-end
     *
     * Publish an entry directly by setting the status.
     *
     * Notify admin.
     */
    public function actionPublishEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();
        craft()->mpEntry->updateStatus($entry->id, BaseElementModel::ENABLED);

        // Updating the status of a never-before published entry does not update
        // either the postDate or the URI slug so we must do it manually.
        craft()->mpEntry->setPostDateAndSlug($entry->id);

        craft()->mpAdmin->notifyAdminOfPublishedEntry($entry);

        $this->redirect('/account/'.$entry->section->handle);
    }

    /**
     * Front-end
     *
     * Unpublish an entry directly by setting the status.
     */
    public function actionUnpublishEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();
        $this->_ensureContributor($entry->author);
        craft()->mpEntry->updateStatus($entry->id, BaseElementModel::DISABLED);

        $this->redirect('/account/'.$entry->section->handle);
    }

    /**
     * Front-end
     *
     * Delete an entry...but not really; just toggle it to "archived".
     */
    public function actionDeleteEntry()
    {
        $this->requireLogin();

        $entry = $this->_getEntry();
        craft()->mpEntry->archiveEntry($entry);
        $this->plugin->logger("[{$this->currentUser}] ({$this->currentUser->id}) deleted [{$entry}] ({$entry->id}) from {$entry->section}", LogLevel::Warning);

        $this->redirect('/account/'.$entry->section->handle);
    }

    /**
     * Front-end
     *
     * Delete an Asset and its associated file.
     */
    public function actionDeleteAsset()
    {
        $this->requireLogin();
        $this->requireAjaxRequest();
        
        $asset = $this->_getAsset();
        craft()->assets->deleteFiles($asset->id);
        $this->plugin->logger("[{$this->currentUser}] ({$this->currentUser->id}) deleted [{$asset}] ({$asset->id})", LogLevel::Warning);

        $this->returnJson(array('success' => true));
    }

	/**
     * Front-end
     *
	 * Preview an entry from the edit page.
	 *
     * Borrowed and adapted from EntriesController.
     *
	 * @throws HttpException
	 * @return null
	 */
	public function actionPreviewEntry()
	{
		$this->requirePostRequest();

        $entry = $this->_getEntryModel();

        // Set the language to the user's preferred locale so DateFormatter returns the right format
        craft()->setLanguage(craft()->getTargetLanguage(true));

        $this->_populateEntryModel($entry);

		$this->_showEntry($entry);
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
            $this->renderTemplate('account/entries/error', array('error' => 'Content ID is required.'));
        }

        $entry = craft()->entries->getEntryById($entryId);

        if (!$entry)
        {
            $this->renderTemplate('account/entries/error', array('error' => 'Content not found.'));
        }

        if ($entry->author->id != $this->currentUser->id)
        {
            $this->renderTemplate('account/entries/error', array('error' => 'You are not authorized to update this content.'));
        }

        return $entry;
    }

    /**
     * Fetch the asset and ensure ownership.
     */
    private function _getAsset()
    {
        $assetId = craft()->request->getParam('assetId');

        if (!$assetId)
        {
            $this->returnErrorJson('File ID is required.');
        }

        $asset = craft()->assets->getFileById($assetId);

        if (!$asset)
        {
            $this->returnErrorJson('File not found.');
        }

        // TODO verify asset source id
        if ($asset->folder->name != $this->currentUser->id)
        {
            $this->returnErrorJson('You are not authorized to delete this file.');
        }

        return $asset;
    
    }

    /**
     * Some functionality is only available to contributors.
     */
    private function _ensureContributor($user)
    {
        if (!$user->isInGroup('contributor'))
        {
            $this->renderTemplate('account/entries/error', array('error' => 'You are not authorized to un-publish content.'));
        }
    }

    // -----------------
    // Preview functions
    // -----------------

	/**
	 * Fetches or creates an EntryModel.
	 *
     * Borrowed and adapted from EntriesController.
	 *
	 * @throws Exception
	 * @return EntryModel
	 */
	private function _getEntryModel()
	{
        $entry = new EntryModel();
        $entry->sectionId = craft()->request->getRequiredPost('sectionId');

		return $entry;
	}

	/**
	 * Populates an EntryModel with post data.
	 *
     * Borrowed and adapted from EntriesController.
	 *
	 * @param EntryModel $entry
	 *
	 * @return null
	 */
	private function _populateEntryModel(EntryModel $entry)
	{
		// Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
		$entry->typeId        = craft()->request->getPost('typeId', $entry->typeId);
		$entry->slug          = craft()->request->getPost('slug', $entry->slug);
		$entry->postDate      = (($postDate   = craft()->request->getPost('postDate'))   ? DateTime::createFromString($postDate,   craft()->timezone) : $entry->postDate);
		$entry->expiryDate    = (($expiryDate = craft()->request->getPost('expiryDate')) ? DateTime::createFromString($expiryDate, craft()->timezone) : null);
		$entry->enabled       = (bool) craft()->request->getPost('enabled', $entry->enabled);
		$entry->localeEnabled = (bool) craft()->request->getPost('localeEnabled', $entry->localeEnabled);

		$entry->getContent()->title = craft()->request->getPost('title', $entry->title);

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$entry->setContentFromPost($fieldsLocation);

		// Author
		$authorId = craft()->request->getPost('author', ($entry->authorId ? $entry->authorId : craft()->userSession->getUser()->id));

		if (is_array($authorId))
		{
			$authorId = isset($authorId[0]) ? $authorId[0] : null;
		}

		$entry->authorId = $authorId;

		// Parent
		$parentId = craft()->request->getPost('parentId');

		if (is_array($parentId))
		{
			$parentId = isset($parentId[0]) ? $parentId[0] : null;
		}

		$entry->parentId = $parentId;

		// Revision notes
		$entry->revisionNotes = craft()->request->getPost('revisionNotes');
	}

	/**
	 * Displays an entry.
	 *
     * Borrowed and adapted from EntriesController.
	 *
	 * @param EntryModel $entry
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _showEntry(EntryModel $entry)
	{
		$section = $entry->getSection();
		$type = $entry->getType();

		if (!$section || !$type)
		{
			Craft::log('Attempting to preview an entry that doesn’t have a section/type', LogLevel::Error);
			throw new HttpException(404);
		}

		craft()->setLanguage($entry->locale);

		if (!$entry->postDate)
		{
			$entry->postDate = new DateTime();
		}

		// Have this entry override any freshly queried entries with the same ID/locale
		craft()->elements->setPlaceholderElement($entry);

		craft()->templates->getTwig()->disableStrictVariables();

        switch ($section->handle)
        {
        case 'letters':
            $template = 'letter';
            break;
        case 'media':
        case 'news':
            $template = 'link';
            break;
        case 'notices':
            $template = 'notice';
            break;
        default:
            $template = $section->handle;
        }

		$this->renderTemplate('submit/_preview/'.$template, array('entry' => $entry, 'preview' => true));
	}
}
