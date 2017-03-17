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
     * Create Stripe Customer and Subscription.
     * Invoked by regular HTTP request.
     */
    public function create($name, $amount, $email, $token)
    {
        $this->_setAPiKey();

        $plan = $this->_create_plan($name, $amount);

        $customer = \Stripe\Customer::create(array(
            'description' => "Monthly Donation from $name",
            'email'       => $email,
            'plan'        => $plan,
            'source'      => $token
        ));

        return $customer;
    }

    //-------------------------------------------------------------------------
    // Private functions
    //-------------------------------------------------------------------------

    /**
     * Create Stripe Plan.
     * Invoked by regular HTTP request.
     */
    private function _create_plan($name, $amount)
    {
        $id = uniqid('monthly.donation.');

		$plan = \Stripe\Plan::create(array(
			'amount'   => $amount,
			'currency' => 'usd',
			'id'       => $id,
			'interval' => 'month',
			'name'     => "Monthly Donation from $name"
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
