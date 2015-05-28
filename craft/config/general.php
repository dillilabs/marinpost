<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/general.php
 */

define('URI_SCHEME', isset($_SERVER['HTTPS']) ? 'https://' : 'http://');
define('SITE_URL', URI_SCHEME . $_SERVER['SERVER_NAME'] . '/');

return array(
    '*' => array(
        'activateAccountFailurePath'      => 'account/register/error',
        'activateAccountSuccessPath'      => 'account/register/welcome',
        'autoLoginAfterAccountActivation' => true,
        'invalidUserTokenPath'            => 'account/login/error',
        'loginPath'                       => 'account/login',
        'logoutPath'                      => 'account/logout',
        'maxUploadFileSize'               => 4194304, // 4mb
        'omitScriptNameInUrls'            => true,
        'setPasswordPath'                 => 'account/password/reset',
        'setPasswordSuccessPath'          => 'account/login',
        'siteName'                        => 'The Marin Post',
        'siteUrl'                         => SITE_URL, // Include URI scheme
        'timezone'                        => 'America/Los_Angeles',
        'useEmailAsUsername'              => true,

        // Not actually environment-specific
        'environmentVariables' => array(
            // But required for the Minimee plugin's "Cache Url" configuration
            'baseUrl' => SITE_URL,
        ),

        // Custom
        'cacheTagDisabled' => true,
    ),
    'dev' => array(
        // 'devMode' => true,
    ),
    'live' => array(
    ),
);
