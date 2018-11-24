<?php

// Include URI scheme in siteUrl.
$uriScheme = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$siteUrl   = $uriScheme.$_SERVER['SERVER_NAME'].'/';

// Skip CSRF protection for Stripe webhook events.
$requestUri           = $_SERVER['REQUEST_URI'];
$enableCsrfProtection = !isset($requestUri) || $requestUri != '/stripeEvent';

return array(
    '*' => array(
        'activateAccountFailurePath'      => 'account/register/error',
        'activateAccountSuccessPath'      => 'account/register/welcome',
        'autoLoginAfterAccountActivation' => true,
        'enableCsrfProtection'            => $enableCsrfProtection,
        'filenameWordSeparator'           => null,
        'invalidUserTokenPath'            => 'account/login/error',
        'loginPath'                       => 'account/login',
        'logoutPath'                      => 'account/logout',
        'maxUploadFileSize'               => 16777216, // 16mb
        'omitScriptNameInUrls'            => true,
        'purgePendingUsersDuration'       => false,
        'setPasswordPath'                 => 'account/password/reset',
        'setPasswordSuccessPath'          => 'account/login',
        'siteName'                        => 'The Marin Post',
        'siteUrl'                         => $siteUrl,
        'timezone'                        => 'America/Los_Angeles',
        'useEmailAsUsername'              => true,
        'userSessionDuration'             => false,

        'environmentVariables' => array(
            // Not actually environment-specific, but required for
            // the Minimee plugin's "Cache Url" configuration.
            'baseUrl' => $siteUrl,
        ),

        // Custom setting used in templates
        // to toggle caching on/off.
        'cacheTagDisabled' => true,
    ),

    'dev' => array(
        // 'devMode' => true,
    ),

    'live' => array(
        // Just the defaults, ma'am.
    ),
);
