<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/mpsubscription/vendor/autoload.php';

class MpSubscriptionController extends BaseController
{
    protected $allowAnonymous = array(
        'actionCurrentIssue',
        'actionStripeEvent',
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
     * CREATE subscription for logged-in User.
     * Regular HTTP request.
     */
    public function actionCreate()
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $plan = craft()->request->getParam('plan');
        if (!$plan)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Subscription plan is required.'));
            return;
        }

        $token = craft()->request->getParam('stripeToken');
        if (!$token)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Credit card is required.'));
            return;
        }

        $user = craft()->userSession->user;
        // $this->plugin->logger("Attempting to create subscription for $user, and subscribe to $plan.");

        try
        {
            craft()->mpSubscription->create($user, $plan, $token);

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

    /**
     * CANCEL subscription of logged-in User.
     * Regular HTTP request.
     */
    public function actionCancel()
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $user = craft()->userSession->user;
        // $this->plugin->logger("Attempting to cancel subscription for $user.");

        try
        {
            craft()->mpSubscription->cancel($user);

            $this->plugin->logger("Successfully canceled subscription for $user.");
            $this->redirectToPostedUrl();
        }
        catch (\Stripe\Error\Base $e)
        {
            $error = $e->getMessage();
            $this->plugin->logger("Failed to cancel subscription for $user: $error", LogLevel::Error);
            craft()->urlManager->setRouteVariables(array('error' => $error));
        }
    }

    /**
     * REACTIVATE canceled subscription of logged-in User.
     * Regular HTTP request.
     */
    public function actionReactivate()
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $user = craft()->userSession->user;
        // $this->plugin->logger("Attempting to reactivate canceled subscription for $user.");

        try
        {
            craft()->mpSubscription->reactivate($user);

            $this->plugin->logger("Successfully reactivate canceled subscription for $user.");
            $this->redirectToPostedUrl();
        }
        catch (\Stripe\Error\Base $e)
        {
            $error = $e->getMessage();
            $this->plugin->logger("Failed to reactivate subscription for $user: $error", LogLevel::Error);
            craft()->urlManager->setRouteVariables(array('error' => $error));
        }
    }

    /**
     * Change subscription PLAN of logged-in User.
     * AJAX request.
     */
    public function actionChangePlan()
    {
        $this->requireLogin();
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $plan = craft()->request->getParam('plan');
        if (!$plan)
        {
            $this->returnErrorJson('Subscription plan is required.');
        }

        $user = craft()->userSession->user;
        // $this->plugin->logger("Attempting to change subscription plan for $user, to $plan.");

        try
        {
            $expirationDate = craft()->mpSubscription->changePlan($user, $plan);

            $this->plugin->logger("Successfully changed subscription plan for $user, to $plan, expiring on $expirationDate.");
            $this->returnJson(array(
                'success'        => true,
                'plan'           => $plan,
                'expirationDate' => $expirationDate
            ));
        }
        catch (\Stripe\Error\Base $e)
        {
            $error = $e->getMessage();
            $this->plugin->logger("Failed to change subscription plan for $user: $error", LogLevel::Error);
            $this->returnErrorJson($error);
        }
    }

    /**
     * Change active CREDIT CARD of logged-in User.
     * AJAX request.
     */
    public function actionChangeCard()
    {
        $this->requireLogin();
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $token = craft()->request->getParam('stripeToken');
        if (!$token)
        {
            craft()->urlManager->setRouteVariables(array('error' => 'Credit card is required.'));
            return;
        }

        $user = craft()->userSession->user;
        // $this->plugin->logger("Attempting to change credit card for $user.");

        try
        {
            $card = craft()->mpSubscription->changeCard($user, $token);

            $this->plugin->logger("Successfully changed credit card for $user, to $card");
            $this->returnJson(array('success' => true, 'card' => $card));
        }
        catch (\Stripe\Error\Base $e)
        {
            $error = $e->getMessage();
            $this->plugin->logger("Failed to change credit card for $user: $error", LogLevel::Error);
            $this->returnErrorJson($error);
        }
    }

    /**
     * Handle anonymous POST from Stripe webhooks.
     */
    public function actionStripeEvent()
    {
        $this->requirePostRequest();

        $input = @file_get_contents('php://input');
        $event = json_decode($input);

        if (! $this->_stripeModeMatchesServerEnv($event->livemode))
        {
            $this->returnJson(array('thanks' => 'but no thanks'));
            return;
        }

        $object = $event->data->object;
        $customerId = $object->object == 'customer' ? $object->id : $object->customer;
        $this->plugin->logger("Received Stripe Event[{$event->type}, {$event->id}] Object[{$object->object}, {$object->id}] Customer[$customerId]");

        switch ($event->type)
        {
        case 'charge.succeeded':
        case 'charge.failed':

        case 'customer.created':
        case 'customer.updated':
        case 'customer.deleted':

        case 'customer.source.created':
        case 'customer.source.updated':
        case 'customer.source.deleted':

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
        case 'customer.subscription.deleted':

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
        case 'customer.subscription.deleted':

        case 'invoice.payment_succeeded':
        case 'invoice.payment_failed':

            // NOTE not all of these events are actually of interest to the handler

            craft()->mpSubscription->handleStripeEvent($event->id);
            break;

        default:
            $this->plugin->logger("Not interested in Stripe {$event->type} Event.");
        }

        // Always polite.
        $this->returnJson(array('thank' => 'you'));
    }

    /**
     * Schedule task to email current issue to subscribed Users.
     *
     * LOCAL-only request.
     *
     * @param string -- daily, weekly or monthly
     */
    public function actionCurrentIssue()
    {
        if (! $this->_isLocalRequest())
        {
            echo 'Local-only function.';
            craft()->end();
        }

        $period = craft()->request->getParam('period');
        if (! $period)
        {
            echo 'period param is required.';
            craft()->end();
        }

        // Paid subscriptions

        $subscribers = craft()->mpSubscription->activeSubscribersForEmailPeriod($period);

        if (count($subscribers) > 0)
        {
            foreach ($subscribers as $user)
            {
                $this->_createTask($period, $user);
            }
        }
        else
        {
            $this->plugin->logger("No subscribers found for $period email alerts.");
        }

        if ($period == 'weekly')
        {
            // Complimentary subscriptions

            $freebies = craft()->mpSubscription->usersWithoutPaidSubscription();

            if (count($freebies) > 0)
            {
                foreach ($freebies as $user)
                {
                    $this->_createTask($period, $user);
                }
            }
            else
            {
                $this->plugin->logger("No free subscriptions found.");
            }
        }

        craft()->end();
    }

    /**
     * Email current issue to logged-in, subscribed User.
     * AJAX request.
     */
    public function actionMyCurrentIssue()
    {
        $this->requireLogin();
        $this->requireAjaxRequest();

        $user = craft()->userSession->user;

        if (craft()->mpSubscription->activeSubscription($user))
        {
            // craft()->mpSubscription->sendEmailToUser($user, $force = true);
            // Use same code path as the regular, automated email.
            $this->_createTask($user->subscriptionFrequency, $user);
            craft()->tasks->runPendingTasks();

            $this->returnJson(array('message' => "Sent to $user"));
        }
        else
        {
            $this->returnJson(array('message' => 'No active subscription.'));
        }
    }

    //-------------------------------------------------------------------------
    // Private functions
    //-------------------------------------------------------------------------

    /**
     * Return true if request is from localhost.
     */
    private function _isLocalRequest()
    {
        return $_SERVER['REMOTE_ADDR'] == '127.0.0.1';
    }

    /**
     * Create Task to send email for period to User.
     */
    private function _createTask($period, $user)
    {
        $klass       = 'MpSubscription';
        $description = "$period subscription for $user";
        $settings    = array('user' => $user);

        $task = craft()->tasks->createTask($klass, $description, $settings);

        if (!$task->hasErrors())
        {
            $this->plugin->logger("Successfully scheduled $klass task: $description.");
        }
        else
        {
            $this->plugin->logger("Failed to schedule $klass task: $description: ".$task->getErrors(), LogLevel::Error);
        }
    }

    /**
     * Return false unless Stripe Mode matches the server environment.
     */
    private function _stripeModeMatchesServerEnv($stripeLiveMode)
    {
        if ($stripeLiveMode)
        {
            if (CRAFT_ENVIRONMENT != 'live')
            {
                $this->plugin->logger('Ignoring Stripe LIVE MODE webhook in non-LIVE Craft environment.');
                return false;
            }
        }
        else // Stripe Test mode
        {
            if (CRAFT_ENVIRONMENT != 'dev')
            {
                $this->plugin->logger('Received Stripe TEST MODE webhook in LIVE Craft environment.');
                return false;
            }
        }

        return true;
    }
}
