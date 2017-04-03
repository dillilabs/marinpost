<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/mpsubscription/vendor/autoload.php';

class MpSubscription_DonationController extends BaseController
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
     * CREATE donation for donor.
     * AJAX request.
     */
    public function actionCreate()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $token = craft()->request->getParam('stripeToken');
        if (!$token)
        {
            $this->returnErrorJson('Credit card info is required.');
        }

        $email = craft()->request->getParam('email');
        if (!$email)
        {
            $this->returnErrorJson('Email address is required.');
        }

        $amount = craft()->request->getParam('amount');
        if (!$amount)
        {
            $this->returnErrorJson('Donation amount is required.');
        }

        $monthly = craft()->request->getParam('monthly');

        try
        {
            craft()->mpSubscription_donation->create($email, $token, $amount, $monthly);

            $this->plugin->logger("Successfully handled $".($amount / 100.0)." donation from $email.");
            $this->returnJson(array('success' => true));
        }
        catch (\Stripe\Error\Base $e)
        {
            $error = $e->getMessage();
            $this->plugin->logger("Stripe errors: failed to handle donation from $email: $error", LogLevel::Error);
            $this->returnErrorJson($error);
        }
        catch (Exception $e)
        {
            $error = $e->getMessage();
            $this->plugin->logger("Unknown error: failed to handle donation from $email: $error", LogLevel::Error);
            $this->returnErrorJson($error);
        }
    }

}
