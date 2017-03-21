<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/mpsubscription/vendor/autoload.php';

class MpSubscription_DontationService extends BaseApplicationComponent
{
    private $plugin;
    private $secretKey;

    function __construct()
    {
        $this->secretKey = craft()->config->get('stripeSecretKey', 'stripe');

        $this->plugin = craft()->plugins->getPlugin('mpsubscription');
    }

    //-------------------------------------------------------------------------
    // Public functions
    //-------------------------------------------------------------------------

    /**
     * Charge email for amount on token (credit card).
     *
     * If monthly then create a monthly Stripe Plan, Customer and Subscription
     * for it.
     *
     * Else create a one-time Stripe Charge.
     */
    public function create($email, $token, $amount, $monthly)
    {
        $this->_setAPiKey();

        if ($monthly)
        {
            $plan = $this->_create_plan($email, $amount);

            \Stripe\Customer::create(array(
                'description' => "Monthly donation from $email",
                'email'       => $email,
                'plan'        => $plan,
                'source'      => $token,
            ));
        }
        else
        {
            \Stripe\Charge::create(array(
                'amount'               => $amount,
                'currency'             => 'usd',
                'description'          => "One-time donation from $email",
                'reciept_email'        => $email,
                'source'               => $token,
                'statement_descriptor' => 'DONATION TO MARINPOST',
            ));
        }

        return true;
    }

    //-------------------------------------------------------------------------
    // Private functions
    //-------------------------------------------------------------------------

    /**
     * Create Stripe Plan.
     * Invoked by regular HTTP request.
     */
    private function _create_plan($email, $amount)
    {
        $uniqueId = uniqid('monthly.donation.');

		$plan = \Stripe\Plan::create(array(
			'amount'               => $amount,
			'currency'             => 'usd',
			'id'                   => $uniqueId,
			'interval'             => 'month',
			'name'                 => "Monthly donation from $email",
            'statement_descriptor' => 'DONATION TO MARINPOST',
		));

        return $plan;
    }

    /**
     * Return Stripe API key.
     */
    private function _setApiKey()
    {
        \Stripe\Stripe::setApiKey($this->secretKey);
    }

}
