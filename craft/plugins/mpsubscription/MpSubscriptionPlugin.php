<?php
namespace Craft;

class MpSubscriptionPlugin extends BasePlugin
{

    /**
     * Semi-smart logger.
     */
    public function logger($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    //-------------------------------------------------------------------------
    // Settings
    //-------------------------------------------------------------------------

    protected function defineSettings()
    {
        return array(
            'forceLog' => array(AttributeType::Bool, 'default' => false),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('mpsubscription/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //-------------------------------------------------------------------------
    // Hooks
    //-------------------------------------------------------------------------

    /**
     * Register route to handle Stripe event webhook.
     */
    public function registerSiteRoutes()
    {
        return array(
            'sendCurrentIssue' => array('action' => 'mpSubscription/currentIssue'),
            'stripeEvent'      => array('action' => 'mpSubscription/stripeEvent'),
        );
    }

    //-------------------------------------------------------------------------
    // Boilerplate
    //-------------------------------------------------------------------------

    public function getName()
    {
        return 'Marin Post Subscription';
    }

    public function getVersion()
    {
        return '1.1.0';
    }

    public function getDeveloper()
    {
        return 'Steve Pedersen';
    }

    public function getDeveloperUrl()
    {
        return 'https://github.com/speder';
    }
}
