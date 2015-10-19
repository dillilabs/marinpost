<?php
namespace Craft;

class MpSubscriptionVariable
{
    //-------------------------------------------------------------------------
    // Public functions
    //-------------------------------------------------------------------------

    /**
     * Return true if current User has a subscription in good standing.
     */
    public function activeSubscription()
    {
        return craft()->mpSubscription->activeSubscription(craft()->userSession->user);
    }

    /**
     * Return array of subscription plans with selection of current User indicated.
     */
    public function listPlans()
    {
        return craft()->mpSubscription->listPlans(craft()->userSession->user);
    }

    /**
     * Return string summary of active card.
     */
    public function cardSummary()
    {
        return craft()->mpSubscription->stripeCardSummary(craft()->userSession->user);
    }

    /**
     * Stripe public key.
     */
    public function publishableKey()
    {
        return craft()->mpSubscription->publishableKey();
    }

    /**
     * Return entries composing current User's current issue.
     * Used ONLY in /account/subscription/simulator.
     */
    public function allEntries()
    {
        return craft()->mpSubscription->entriesForUser(craft()->userSession->user);
    }

    /**
     * Return textual representation of the current issue's period.
     * Used in email template and /account/subscription/simulator.
     */
    public function currentIssuePeriod($user = false)
    {
        if (!$user) {
            $user = craft()->userSession->user;
        }
        return craft()->mpSubscription->currentIssuePeriod($user);
    }

    /**
     * Return title of current issue.
     * Used in email template and /account/subscription/simulator.
     */
    public function currentIssueTitle($user = false)
    {
        if (!$user) {
            $user = craft()->userSession->user;
        }
        return craft()->mpSubscription->currentIssueTitle($user);
    }
}
