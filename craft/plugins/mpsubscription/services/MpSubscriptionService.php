<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/mpsubscription/vendor/autoload.php';

class MpSubscriptionService extends BaseApplicationComponent
{
    private $plugin;
    private $secretKey;
    private $publishableKey;

    function __construct()
    {
        $this->secretKey      = craft()->config->get('stripeSecretKey',      'stripe');
        $this->publishableKey = craft()->config->get('stripePublishableKey', 'stripe');

        $this->plugin = craft()->plugins->getPlugin('mpsubscription');
    }

    //-------------------------------------------------------------------------
    // Public functions
    //-------------------------------------------------------------------------

    /**
     * Return Stripe PUBLISHABLE KEY.
     */
    public function publishableKey()
    {
        return $this->publishableKey;
    }

    /**
     * Return true if User has an active SUBSCRIPTION.
     */
    public function activeSubscription($user)
    {
        return $this->_hasStripeCustomerId($user) && $this->_hasActiveSubscription($user);
    }

    /**
     * Return list of all subscription PLANS.
     */
    public function listPlans($user)
    {
        $this->_setAPiKey();

        $plans = array();

        try
        {
            $object = \Stripe\Plan::all();

            $currentPlanId = $this->activeSubscription($user) ?  $this->_currentPlan($user)->id : null;
            $defaultPlan = 'Quarter';

            foreach ($object->data as $plan)
            {
                $description = $plan->interval == 'year' ? 'Year' : ($plan->interval_count == '3' ? 'Quarter' : 'Month');

                $plans[$description] = array(
                    'id'          => $plan->id,
                    'amount'      => $plan->amount,
                    'description' => $description,
                    'selected'    => $currentPlanId ? $plan->id == $currentPlanId : $description == $defaultPlan
                );
            }

            // Sort: Monthly > Quarterly > Yearly
            sort($plans);
        }
        catch (\Stripe\Error\Base $e)
        {
            $this->plugin->logger("Failed to get list of Stripe subscription plans: {$e->getMessage()}", LogLevel::Error);
        }

        return $plans;
    }

    /**
     * Create SUBSCRIPTION with plan for User with credit card.
     * Invoked by regular HTTP request.
     */
    public function create($user, $plan, $token)
    {
        if ($this->_hasStripeCustomerId($user)) {
            $this->changeCard($user, $token);
            $this->_createNewSubscription($user, $plan);
            return;
        }

        $this->_setAPiKey();

        $customer = \Stripe\Customer::create(array(
            'description' => "Subscription for {$user->name}",
            'email'       => $user->email,
            'plan'        => $plan,
            'source'      => $token
        ));

        $card = $customer->sources->data[0];

        $userFields = craft()->request->getParam('fields', array());

        $this->_updateUser($user, array(
            'stripeCustomerId'           => $customer->id,
            'stripeCard'                 => $this->_stripeCard($card),
            'subscriptionExpirationDate' => $customer->subscriptions->data[0]->current_period_end,
            'subscriptionFrequency'      => $userFields['subscriptionFrequency'],
            'subscriptionContent'        => $userFields['subscriptionContent'],
            'subscriptionLocations'      => $userFields['subscriptionLocations'],
            'subscriptionTopics'         => $userFields['subscriptionTopics'],
            'subscriptionAuthors'        => $userFields['subscriptionAuthors'],
            'subscriptionLetters'        => $userFields['subscriptionLetters'],
        ));
    }

    /**
     * Change subscription PLAN of User.
     * Invoked by AJAX request.
     */
    public function changePlan($user, $plan)
    {
        $this->_setAPiKey();

        $customer = \Stripe\Customer::retrieve($user->stripeCustomerId);
        $subscriptionId = $customer->subscriptions->all()->data[0]->id;
        $subscription = $customer->subscriptions->retrieve($subscriptionId);
        $subscription->plan = $plan;
        $subscription->save();

        $expirationDate = $subscription->current_period_end;
        $this->_updateUser($user, array(
            'subscriptionExpirationDate' => $expirationDate
        ));

        return date('F j, Y', $expirationDate);
    }

    /**
     * Change active CREDIT CARD of User.
     * Invoked by AJAX request.
     */
    public function changeCard($user, $token)
    {
        $this->_setAPiKey();

        $customer = \Stripe\Customer::retrieve($user->stripeCustomerId);
        $customer->source = $token;
        $customer = $customer->save();

        $card = $customer->sources->data[0];

        $this->_updateUser($user, array(
            'stripeCard' => $this->_stripeCard($card)
        ));

        return $this->stripeCardSummary($user);
    }

    /**
     * CANCEL subscription of User.
     *
     * Invoked from one of two actions -- both regular HTTP requests:
     *
     *     1. mpsubscription/cancel
     *
     *     2. mpuser/delete -- where suspend=true
     */
    public function cancel($user, $suspend = false)
    {
        $this->_setAPiKey();

        $customer = \Stripe\Customer::retrieve($user->stripeCustomerId);
        $subscriptionId = $customer->subscriptions->all()->data[0]->id;
        $customer->subscriptions->retrieve($subscriptionId)->cancel(array(
            'at_period_end' => true
        ));

        $content = array('subscriptionCanceled' => true);

        if ($suspend)
        {
            $content['subscriptionSuspended'] = true;
        }

        $this->_updateUser($user, $content);
    }

    /**
     * REACTIVATE canceled subscription of User.
     * Invoked by regular HTTP request.
     */
    public function reactivate($user)
    {
        $this->_setAPiKey();

        $customer = \Stripe\Customer::retrieve($user->stripeCustomerId);
        $subscriptionId = $customer->subscriptions->all()->data[0]->id;
        $subscription = $customer->subscriptions->retrieve($subscriptionId);
        $subscription->plan = $subscription->plan->id;
        $subscription->save();

        $this->_updateUser($user, array(
            'subscriptionCanceled' => false
        ));
    }

    /**
     * Retrieve Stripe event -- received from Stripe webhook.
     */
    public function handleStripeEvent($eventId)
    {
        $this->_setAPiKey();

        try
        {
            $event = \Stripe\Event::retrieve($eventId);

            $object = $event->data->object;
            $customerId = $object->object == 'customer' ? $object->id : $object->customer;
            $user = $this->_getUserByStripeCustomerId($customerId);
            $this->plugin->logger("Retrieved Stripe Event[{$event->type}, {$event->id}] Object[{$object->object}, {$object->id}] Customer[$customerId] User[$user]");

            if (empty($user))
            {
                $this->plugin->logger("Cannot find User for Stripe Customer[$customerId]", LogLevel::Error);
            }

            switch ($event->type)
            {
            case 'customer.deleted':
                if ($user)
                {
                    $this->plugin->logger("Removing Stripe Customer for $user.");
                    $this->_updateUser($user, array('stripeCustomerId' => ''));
                }
                break;

            case 'customer.subscription.updated':
                if ($user)
                {
                    $expirationDate = $object->current_period_end;
                    $this->plugin->logger("Updating subscription expiration date for $user, to {$expirationDate}.");
                    $this->_updateUser($user, array('subscriptionExpirationDate' => $expirationDate));
                }
                break;

            case 'customer.subscription.deleted':
                if ($user)
                {
                    $this->plugin->logger("Expiring subscription for $user.");
                    $this->_updateUser($user, array('subscriptionExpirationDate' => ''));
                }
                break;

#           case 'charge.failed':
            case 'invoice.payment_failed':
                if ($user)
                {
                    $this->plugin->logger("Notifying $user of payment failure.", LogLevel::Warning);
                    $this->_notifyUserOfPaymentFailure($user);
                }
                break;

            default:
                $this->plugin->logger("No action taken for Stripe {$event->type} Event.");
                return;
            }
        }
        catch (\Stripe\Error\Base $e)
        {
            $message = "Failed to handle Stripe event: {$e->getMessage()}";
            $this->plugin->logger($message, LogLevel::Error);
            $this->_notifyAdminOfError('Stripe error', $message);
        }
        catch (Exception $e)
        {
            $message = "Failed to handle Stripe event: {$e->getMessage()}";
            $this->plugin->logger($message, LogLevel::Error);
            $this->_notifyAdminOfError('Unknown error in handleStripeEvent()', $message);
        }
    }

    /**
     * Return list of Users who have:
     *
     *   1. an active subscription
     *   2. the given email frequency
     *   3. have subscribed content -- either 'default' or one or more of:
     *
     *     - locations
     *     - topics
     *     - authors
     *     - letters
     *
     * @param string -- daily, weekly or monthly
     */
    public function activeSubscribersForEmailPeriod($period)
    {
        $users = array();
        $today = date('Y-m-d');

        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->stripeCustomerId           = 'cus_*'; // 'not null'
        $criteria->subscriptionExpirationDate = "> $today";
        $criteria->subscriptionSuspended      = 'not 1';
        $criteria->subscriptionFrequency      = $period;

        // OPTIMIZE do this via relatedTo...
        foreach ($criteria->find() as $user)
        {
            if ($this->_isSubscribedToContent($user))
            {
                array_push($users, $user);
            }
        }

        return $users;
    }

    /**
     * Return list of Users with non-paid, complimentary subscriptions.
     */
    public function usersWithoutPaidSubscription()
    {
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->stripeCustomerId           = ':empty:';
        $criteria->subscriptionSuspended      = 'not 1';
        $criteria->limit                      = null;

        return $criteria->find();
    }

    /**
     * Send email to User for configured email period.
     *
     * IF daily
     *   AND no entries
     *   AND force != true
     * THEN don't send email.
     */
    public function sendEmailToUser($user, $force = false)
    {
        $period  = $user->subscriptionFrequency;
        $entries = $this->entriesForUser($user);

        if (empty($entries) && $period == 'daily' && !$force)
        {
            $this->plugin->logger("No entries for $period, so NOT sending email to $user.");
            return;
        }

        if ($this->_sendCurrentIssueToUser($user, $entries))
        {
            $this->plugin->logger("Successfully sent $period email to $user.");
        }
        else
        {
            $this->plugin->logger("Failed to send $period email to $user.", LogLevel::Error);
        }
    }

    /**
     * Get entries for given User.
     * NOTE public visibility required when called by MpSubscriptionVariable.
     */
    public function entriesForUser($user)
    {
        if (! $this->_isSubscribedToContent($user))
        {
            $this->plugin->logger("No subscribed content for $user.");
            return array();
        }

        $period = $user->subscriptionFrequency;
        list($startDate, $endDate) = $this->_startAndEndDates($period);

        $filterBy = array();

        if ($user->subscriptionContent != 'default')
        {
            $filterBy['locations'] = $user->subscriptionLocations->ids(); 
            $filterBy['topics']    = $user->subscriptionTopics->ids(); 
            $filterBy['authors']   = $user->subscriptionAuthors->ids(); 
            $filterBy['letters']   = $user->subscriptionLetters->count();
        }

        $entryIds = craft()->mpFilter->entryIdsForSubscription($startDate, $endDate, $filterBy);

        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->id = $entryIds;
        $criteria->order = 'postDate desc';
        $entries = $criteria->find();

        return $entries;
    }

    /**
     * Return title of User's current issue, eg:
     *
     *   Weekly Update ~ Selected Posts
     */
    public function currentIssueTitle($user)
    {
        $period = $user->subscriptionFrequency->label;
        $content = $user->subscriptionContent == 'default' ? 'All' : 'Selected';

        return "$period Update ~ $content Posts";
    }

    /**
     * Return textual representation of the User's current issue's period, eg:
     *
     *   February 1 - 7, 2016
     */
    public function currentIssuePeriod($user)
    {
        $period = $user->subscriptionFrequency;
        $dates = $this->_startAndEndDates($period);

        switch ($period)
        {
        case 'daily':
            return $dates[0]->format('F j, Y');
            break;

        case 'monthly':
            return $dates[0]->format('F Y');
            break;

        default: // weekly
            $dates[1]->modify('-1 Day'); // because the programmatic end-date is EXCLUSIVE

            $endDateFormat = $dates[0]->format('F') == $dates[1]->format('F') ? 'j, Y' : 'F j, Y';

            return "{$dates[0]->format('F j')} - {$dates[1]->format($endDateFormat)}";
        }
    }

    /**
     * Return string representing brand, last4 and exp date of User's card.
     */
    public function stripeCardSummary($user)
    {
        foreach ($user->stripeCard as $row)
        {
            if ($row['key'] == 'Card Summary')
            {
                return $row['value'];
            }
        }

        return 'unknown';
    }

    //-------------------------------------------------------------------------
    // Private functions
    //-------------------------------------------------------------------------

    /**
     * Return true if User has a Stripe Customer ID.
     */
    private function _hasStripeCustomerId($user)
    {
        return !empty($user->stripeCustomerId);
    }

    /**
     * Return true if User has an active subscription.
     * NOTE "active" includes canceled but not yet expired.
     */
    private function _hasActiveSubscription($user)
    {
        $today = date('Y-m-d');
        return $user->subscriptionExpirationDate >= $today;
    }

    /**
     * Return Stripe API key.
     */
    private function _setApiKey()
    {
        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    /**
     * Return User with given Stripe Customer ID.
     */
    private function _getUserByStripeCustomerId($stripeCustomerId)
    {
        if (empty(trim($stripeCustomerId)))
        {
            return null;
        }

        $criteria = craft()->elements->getCriteria(ElementType::User, array(
            'stripeCustomerId' => $stripeCustomerId,
        ));

        return $criteria->first();
    }

    /**
     * Create new SUBSCRIPTION with plan for User.
     */
    private function _createNewSubscription($user, $plan)
    {
        $this->_setAPiKey();
        $customer = \Stripe\Customer::retrieve($user->stripeCustomerId);
        $subscription = $customer->subscriptions->create(array(
            'plan' => $plan
        ));

        $this->_updateUser($user, array(
            'subscriptionExpirationDate' => $subscription->current_period_end,
            'subscriptionCanceled'       => false,
            'subscriptionSuspended'      => false
        ));
    }

    /**
     * Return current subscription PLAN of User.
     */
    private function _currentPlan($user)
    {
        $this->_setAPiKey();

        try
        {
            $customer = \Stripe\Customer::retrieve($user->stripeCustomerId);
            return $customer->subscriptions->data[0]->plan;
        }
        catch (\Stripe\Error\Base $e)
        {
            $this->plugin->logger("Failed to get subscription plan for $user: {$e->getMessage()}", LogLevel::Error);
            return null;
        }
    }

    /**
     * Update User content fields.
     */
    private function _updateUser($user, $content)
    {
        $user->setContentFromPost($content);
        $saved = craft()->users->saveUser($user);

        if (! $saved)
        {
            $this->plugin->logger("Failed to save user: $user, content: ".print_r($content, true), LogLevel::Error);
        }

        return $saved;
    }

    /**
     * Return data of Stripe card formatted as an array of arrays.
     */
    private function _stripeCard($card)
    {
        $data = array();

        $data[] = array('col1' => 'Cardholder Name', 'col2' => $card->name);
        $data[] = array('col1' => 'Address 1',       'col2' => $card->address_line1);
        $data[] = array('col1' => 'Address 2',       'col2' => $card->address_line2);
        $data[] = array('col1' => 'City',            'col2' => $card->address_city);
        $data[] = array('col1' => 'State',           'col2' => $card->address_state);
        $data[] = array('col1' => 'Country',         'col2' => $card->address_country);
        $data[] = array('col1' => 'Zip',             'col2' => $card->address_zip);
        $data[] = array('col1' => 'Card Brand',      'col2' => $card->brand);
        $data[] = array('col1' => 'Card Last4',      'col2' => $card->last4);
        $data[] = array('col1' => 'Card Exp Month',  'col2' => $card->exp_month);
        $data[] = array('col1' => 'Card Exp Year',   'col2' => $card->exp_year);
        $data[] = array('col1' => 'Card Country',    'col2' => $card->country);
        $data[] = array('col1' => 'Address 1 Check', 'col2' => $card->address_line1_check);
        $data[] = array('col1' => 'Zip Check',       'col2' => $card->address_zip_check);
        $data[] = array('col1' => 'CVC Check',       'col2' => $card->cvc_check);

        $data[] = array('col1' => 'Card Summary',    'col2' => $card->brand.' *-'.$card->last4.', exp: '.$card->exp_month.'/'.$card->exp_year);

        return $data;
    }

    /**
     * Return true if User is subscribed to anything.
     */
    private function _isSubscribedToContent($user)
    {
        if ($user->subscriptionContent == 'default')
        {
            return true;
        }
        else // custom
        {
            return $user->subscriptionLocations->total() > 0 || $user->subscriptionTopics->total() > 0 || $user->subscriptionAuthors->total() > 0 || $user->subscriptionLetters->count() > 0;
        }
    }

    /**
     * Return start- and end- dates for email period.
     */
    private function _startAndEndDates($period)
    {
        $today = new DateTime();
        $dates = array();

        switch ($period)
        {
        case 'daily':
            array_push($dates,
                new DateTime('-1 day'),
                $today
            );
            break;

        case 'monthly':
            array_push($dates,
                (new DateTime('First Day of Last Month')),
                (new DateTime('First Day of This Month'))
            );
            break;

        default: // weekly
            $daysFromMonday = $today->format('N') - 1;
            $lastMonday = new DateTime("- $daysFromMonday Days");
            $mondayLastWeek = new DateTime("- $daysFromMonday Days - 1 Week");

            array_push($dates,
                $mondayLastWeek,
                $lastMonday
            );
        }

        return $dates;
    }

    /**
     * Send summary email to User.
     */
    private function _sendCurrentIssueToUser($user, $entries)
    {
        $savePath = craft()->path->getTemplatesPath();

        craft()->path->setTemplatesPath(
            craft()->path->getPluginsPath().'mpsubscription/templates'
        );

        $email = new EmailModel();
        $email->toEmail = $user->email;
        $email->subject = "{$user->subscriptionFrequency->label} Update from the Marin Post";
        $email->htmlBody = craft()->templates->render('entries', array(
            'user'    => $user,
            'entries' => $entries
        ));

        craft()->path->setTemplatesPath($savePath);

        return craft()->email->sendEmail($email);
    }

    /**
     * Send email to User regarding payment failure.
     */
    private function _notifyUserOfPaymentFailure($user)
    {
        $savePath = craft()->path->getTemplatesPath();

        craft()->path->setTemplatesPath(
            craft()->path->getPluginsPath().'mpsubscription/templates'
        );

        $email = new EmailModel();
        $email->toEmail = $user->email;
        $email->subject = 'Marin Post Subscription Payment Failed';

        $email->htmlBody = craft()->templates->render('payment_failure', array(
            'user' => $user
        ));

        $card = $this->stripeCardSummary($user);

        $body  = "A charge to your $card credit card has failed.\n\n";
        $body .= 'Please go to '.craft()->getSiteUrl()."account/subscription\n";
        $body .= 'and update your credit card info.';
        $email->body = $body;

        craft()->path->setTemplatesPath($savePath);

        if (craft()->email->sendEmail($email))
        {
            $this->plugin->logger("Successfully sent payment failure email to $user.");
        }
        else
        {
            $this->plugin->logger("Failed to send payment failure email to $user.", LogLevel::Error);
        }
    }

    /**
     * Send email to admin(s) regarding Stripe error.
     */
    private function _notifyAdminOfError($subject, $body)
    {
        $email = new EmailModel();

        $emailSettings = craft()->email->getSettings();
        $email->toEmail = $emailSettings['emailAddress'];

        $adminPlugin = craft()->plugins->getPlugin('mpadmin');
        if (!empty($adminPlugin->settings->adminEmail))
        {
            $email->cc = array(
                array('name' => 'Marin Post Admin', 'email' => $adminPlugin->settings->adminEmail)
            );
        }

        $email->subject = $subject.' on '.craft()->request->hostName;
        $email->body = $body;

        if (craft()->email->sendEmail($email))
        {
            $this->plugin->logger('Successfully sent email to admin.');
        }
        else
        {
            $this->plugin->logger('Failed to send email to admin.', LogLevel::Error);
        }
    }
}
