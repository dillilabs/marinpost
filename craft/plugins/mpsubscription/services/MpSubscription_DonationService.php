<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/mpsubscription/vendor/autoload.php';

class MpSubscription_DonationService extends BaseApplicationComponent
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
     *
     * NOTE on Stripe "reciept email":
     *
     * The email address to send this charge's receipt to. The receipt will not
     * be sent until the charge is paid. If this charge is for a customer, the
     * email address specified here will override the customer's email address.
     * Receipts will not be sent for test mode charges. If receipt_email is
     * specified for a charge in live mode, a receipt will be sent regardless
     * of your email settings.
     *
     * We also support saving a customer’s default email address, and can
     * automatically send receipts to that email address whenever your customer
     * makes a payment. This is useful for recurring payments. In this case,
     * you don’t need to pass in a receipt_email each time you charge your
     * customer. To enable this, you’ll want to set an email when creating a
     * customer, and then turn on customer receipts from your email settings by
     * checking the box to “Email customers for successful payment.”
     */
    public function create($email, $token, $amount, $monthly)
    {
        $this->_setAPiKey();

        $metadata = $this->_retrieve_metadata_from_token($token);
        $name     = $metadata['name'];

        if ($monthly)
        {
            $plan = $this->_create_plan($name, $amount);

            \Stripe\Customer::create(array(
                'description' => "Monthly donation from $name",
                'email'       => $email,
                'metadata'    => $metadata,
                'plan'        => $plan,
                'source'      => $token,
            ));
        }
        else // one-time donation
        {
            \Stripe\Charge::create(array(
                'amount'               => $amount,
                'currency'             => 'usd',
                'description'          => "One-time donation from $name",
                'metadata'             => array('email' => $email),
                'receipt_email'        => $email,
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
     * Get metadata used to generate Stripe token.
     */
    private function _retrieve_metadata_from_token($token)
    {
        $object = \Stripe\Token::retrieve($token);

        $metadata = array(
            'name'            => $object->card->name,
            'address_line1'   => $object->card->address_line1,
            'address_line2'   => $object->card->address_line2,
            'address_city'    => $object->card->address_city,
            'address_state'   => $object->card->address_state,
            'address_zip'     => $object->card->address_zip,
            'address_country' => $object->card->address_country,
        );

        return $metadata;
    }

    /**
     * Create Stripe Plan for monthly donation.
     */
    private function _create_plan($name, $amount)
    {
        $uniqueId = uniqid('monthly.donation.');

		$plan = \Stripe\Plan::create(array(
			'amount'               => $amount,
			'currency'             => 'usd',
			'id'                   => $uniqueId,
			'interval'             => 'month',
			'name'                 => "Monthly donation from $name",
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
