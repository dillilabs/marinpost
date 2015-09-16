<?php
namespace Craft;

class MpAdminController extends BaseController
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpadmin');
    }

    /**
     * Control panel (admin only)
     *
     * Block user:
     *
     *      suspend User
     *      assign User to Blocked group
     *      unpublish User entries
     *      archive User entries
     */
    public function actionBlockUser()
    {
        $this->requireAdmin();

        $user = $this->_getUser();
        craft()->users->suspendUser($user);
        $this->_assignUserToGroup($user, 'blocked');
        $this->_unpublishAndArchiveUserEntries($user);

        craft()->userSession->setNotice(Craft::t('User blocked.'));
        $this->renderTemplate('mpAdmin/blocked');
    }

    /**
     * Control panel (admin or admin assistant only)
     *
     * Export User email addresses in .csv or .tab format.
     */
    public function actionExportEmailAddresses()
    {
        craft()->mpAdmin->requireAdminOrAdminAssistant();

        $group = craft()->request->getParam('group', 'contributor');
        $criteria = craft()->elements->getCriteria(ElementType::User, array(
            'group' => $group,
            'limit' => null,
        ));
        $users = $criteria->find();

        $format = craft()->request->getParam('format', 'tab');
        $fieldSep = $format == 'csv' ? ',' : "\t";
        $content = array();
        $fields = array('Primary Email', 'First Name', 'Last Name', 'Display Name');
        array_push($content, implode($fieldSep, $fields));

        foreach ($users as $user)
        {
            $fields = array($user->email, $user->firstName, $user->lastName, $user->fullName);
            array_push($content, implode($fieldSep, $fields));
        }

        craft()->request->sendFile("marinpost-$group-email-addresses.$format", implode("\n", $content), array('forceDownload' => true));
    }

    /**
     * Control panel (admin or admin assistant only)
     *
     * Login as selected User.
     *
     * NOTE to prevent privilege escalation, an admin assistant may not login as an admin.
     *
     * TODO handle case of no id param, or no selectedUser
     */
    public function actionLoginAsUser()
    {
        craft()->mpAdmin->requireAdminOrAdminAssistant();

        $currentUser = craft()->userSession->user;
        $selectedUser = $this->_getUser();

        if ($currentUser->admin || !$selectedUser->admin)
        {
            $this->plugin->logger("{$currentUser->email} logging in as {$selectedUser->email}", LogLevel::Warning);
            craft()->userSession->loginByUserId($selectedUser->id, false, false);
            $this->redirectToPostedUrl();
        }
        else
        {
            throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
        }
    }

    /**
     * Control panel (admin only)
     *
     * Restore an Entry to it's state prior to being archived (ie "deleted").
     */
    public function actionRestoreEntry()
    {
        $this->requireAdmin();

        $entryId = craft()->request->getParam('id');
        $criteria = craft()->elements->getCriteria(ElementType::Entry, array(
            'id'       => $entryId,
            'archived' => true,
        ));
        $entry = $criteria->first();
        craft()->mpEntry->unarchiveEntry($entry);

        craft()->userSession->setNotice(Craft::t('Archived entry restored.'));
        $this->renderTemplate('mpAdmin/archived');
    }

    /**
     * Control panel (admin or admin assistant only)
     *
     * Return list of Entries authored by User.
     */
    public function actionUserEntries()
    {
        craft()->mpAdmin->requireAdminOrAdminAssistant();

        $user = $this->_getUser();
        $criteria = craft()->elements->getCriteria(ElementType::Entry, array(
            'authorId' => $user->id,
            'status'   => null,
            'limit'    => null,
        ));
        $entries = $criteria->find();
        $archivedEntries = $criteria->archived(true)->find();
        $entries = array_merge($entries, $archivedEntries);

        $this->renderTemplate('mpAdmin/entries', array('selectedUser' => $user, 'entries' => $entries));
    }

    // -----------------
    // Private functions
    // -----------------

    private function _assignUserToGroup($user, $groupHandle)
    {
        $deletedGroup = craft()->userGroups->getGroupByHandle($groupHandle);
        craft()->userGroups->assignUserToGroups($user->id, $deletedGroup->id);
    }

    private function _getUser()
    {
        $userId = craft()->request->getParam('id');
        $user = craft()->users->getUserById($userId);
        return $user;
    }

    private function _unpublishAndArchiveUserEntries($user)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry, array(
            'authorId' => $user->id,
            'status'   => BaseElementModel::ENABLED,
            'limit'    => null,
        ));
        $entries = $criteria->find();

        foreach ($entries as $entry)
        {
            craft()->mpEntry->updateStatus($entry->id, BaseElementModel::DISABLED);
            craft()->mpEntry->archiveEntry($entry);
        }
    }
}
