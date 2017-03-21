<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/mpsubscription/vendor/autoload.php';

class MpSubscription_DontationController extends BaseController
{
    protected $allowAnonymous = array(
        'actionCreate',
    );

    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('mpsubscription');
    }

    //-------------------------------------------------------------------------
    // Actions
    //-------------------------------------------------------------------------

    /**
     * CREATE subscription for donor.
     * Regular HTTP request.
     */
    public function actionCreate()
    {
        $this->requirePostRequest();

        $email = craft()->request->getParam('email');
        if (!$email)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Email address is required.'));
            return;
        }

        $token = craft()->request->getParam('stripeToken');
        if (!$token)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Credit card info is required.'));
            return;
        }

        $amount = craft()->request->getParam('amount');
        if (!$amount)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Dollar amount is required'));
            return;
        }

        $monthly = craft()->request->getParam('monthly');

        try
        {
            craft()->mpSubscription_donation->create($email, $token, $amount, $monthly);

            $this->plugin->logger("Successfully created subscription for $user, and subscribed to $plan.");
            $this->redirectToPostedUrl();
        }
        catch (\Stripe\Error\Base $e)
        {
            $error = $e->getMessage();
            $this->plugin->logger("Failed to create subscription for $user: $error", LogLevel::Error);
            craft()->urlManager->setRouteVariables(array('error' => $error));
        }
    }

    //-------------------------------------------------------------------------
    // Private functions
    //-------------------------------------------------------------------------
}
