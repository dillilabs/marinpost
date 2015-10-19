<?php
namespace Craft;

class MpUserController extends BaseController
{
    protected $allowAnonymous = array('actionSendActivationEmail');

    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpuser');
    }

    /**
     * Front-end, public
     *
     * User can trigger an activation email from the front-end.
     */
    public function actionSendActivationEmail()
    {
        $error = false;
        $email = craft()->request->getParam('email');

        if (!$email)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Email address is required.'));
            return;
        }

        $user = craft()->users->getUserByEmail($email);

        if (!$user)
        {
            craft()->urlManager->setRouteVariables(array('error' => "Cannot find User account associated with $email."));
            return;
        }

        if (!in_array($user->status, array(UserStatus::Active, UserStatus::Pending)))
        {
            craft()->urlManager->setRouteVariables(array('error' => "Cannot find User account associated with $email."));
            return;
        }

        craft()->users->sendActivationEmail($user);
        $this->redirectToPostedUrl();
    }

    /**
     * Front-end, logged-in
     *
     * User can delete their own account...but not really:
     *
     *      cancel User's subscription, if found
     *      suspend User
     *      assign User to Deleted group
     */
    public function actionDeleteAccount()
    {
        $this->requireLogin();

        $user = craft()->userSession->user;

        if (craft()->mpSubscription->activeSubscription($user)) {
            // User has an active subscription
            // so cancel it, and suspend email alerts.
            $this->plugin->logger("$user is deleting their account, so we're also canceling their subscription and suspending alerts", LogLevel::Warning);
            craft()->mpSubscription->cancel($user, true);
        }

        craft()->users->suspendUser($user);
        $this->_assignUserToGroup($user, 'deleted');
        $this->plugin->logger("$user deleted their account", LogLevel::Warning);

        craft()->userSession->logout();
        $this->redirectToPostedUrl();
    }

    // -----------------
    // Private functions
    // -----------------

    private function _assignUserToGroup($user, $groupHandle)
    {
        $deletedGroup = craft()->userGroups->getGroupByHandle($groupHandle);
        craft()->userGroups->assignUserToGroups($user->id, $deletedGroup->id);
    }
}
