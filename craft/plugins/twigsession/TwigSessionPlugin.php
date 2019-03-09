<?php
/**
 * Twig Session plugin for Craft CMS
 *
 * HTTP Session
 *
 * @author    Dilli Labs
 * @copyright Copyright (c) 2019 Dilli Labs
 * @link      https://www.dillilabs.com
 * @package   TwigSession
 * @since     1.0.0
 */

namespace Craft;

class TwigSessionPlugin extends BasePlugin
{
    /**
     * @return mixed
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
         return Craft::t('Twig Session');
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return Craft::t('HTTP Session');
    }

    /**
     * @return string
     */
    public function getDocumentationUrl()
    {
        return 'https://github.com/dillilabs/twigsession/blob/master/README.md';
    }

    /**
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/dillilabs/twigsession/master/releases.json';
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    /**
     * @return string
     */
    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * @return string
     */
    public function getDeveloper()
    {
        return 'Dilli Labs';
    }

    /**
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'https://www.dillilabs.com';
    }

    /**
     * @return bool
     */
    public function hasCpSection()
    {
        return false;
    }

    /**
     */
    public function onBeforeInstall()
    {
    }

    /**
     */
    public function onAfterInstall()
    {
    }

    /**
     */
    public function onBeforeUninstall()
    {
    }

    /**
     */
    public function onAfterUninstall()
    {
    }
}