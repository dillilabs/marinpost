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

        $token = craft()->request->getParam('stripeToken');
        if (!$token)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Credit card is required.'));
            return;
        }

        try
        {
            craft()->mpSubscription_donation->create($token);

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
