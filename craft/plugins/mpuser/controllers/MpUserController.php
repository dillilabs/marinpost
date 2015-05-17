<?php
namespace Craft;

class MpUserController extends BaseController
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpuser');
    }

    /**
     * Front-end
     *
     * User can delete their own account...but not really:
     *
     *      suspend User
     *      assign User to Deleted group
     */
    public function actionDeleteAccount()
    {
        $this->requireLogin();

        $user = craft()->userSession->user;

        $this->_suspendUser($user);
        $this->_assignUserToGroup($user, 'deleted');
        $this->plugin->logger("[{$user}] ({$user->id}) deleted their account", LogLevel::Warning);

        craft()->userSession->logout();
        $this->redirectToPostedUrl();
    }

    /**
     * Control panel (admin only)
     *
     * Block user:
     *
     *      suspend User
     *      assign User to Blocked group
     *      unpublish User entries
     */
    public function actionBlockUser()
    {
        $this->requireAdmin();

        $user = $this->_getUser();

        $this->_suspendUser($user);
        $this->_assignUserToGroup($user, 'blocked');
        $this->_unpublishUserEntries($user);

        $this->redirectToPostedUrl();
    }

    // -----------------
    // Private functions
    // -----------------

    private function _suspendUser($user)
    {
        craft()->users->suspendUser($user);
    }

    private function _assignUserToGroup($user, $groupHandle)
    {
        $deletedGroup = craft()->userGroups->getGroupByHandle($groupHandle);
        craft()->userGroups->assignUserToGroups($user->id, $deletedGroup->id);
    }

    private function _unpublishUserEntries($user)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->authorIds = $user->id;
        $criteria->status = BaseElementModel::ENABLED;
        $entries = $criteria->find();

        foreach ($entries as $entry)
        {
            craft()->mpEntry->updateStatus($entry->id, BaseElementModel::DISABLED);
        }
    }

    private function _getUser()
    {
        $userId = craft()->request->getParam('id');
        $user = craft()->users->getUserById($userId);
        return $user;
    }
}
