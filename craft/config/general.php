<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/general.php
 */

return array(
    '*' => array(
        'activateAccountFailurePath' => 'account/register/error',
        'activateAccountSuccessPath' => 'account/register/welcome',
        'autoLoginAfterAccountActivation' => true,
        'invalidUserTokenPath' => 'account/login/error',
        'loginPath' => 'account/login',
        'logoutPath' => 'account/logout',
        'maxUploadFileSize' => 4194304,
        'omitScriptNameInUrls' => true,
        'setPasswordPath' => 'account/password/reset',
        'setPasswordSuccessPath' => 'account/login',
        'siteName' => 'The Marin Post',
        'timezone' => 'America/Los_Angeles',
        'useEmailAsUsername' => true,
    ),
    'dev' => array(
//      'devMode' => true,
        'siteUrl' => 'http://dev.marinpost.org',
    ),
    'live' => array(
        'siteUrl' => 'http://marinpost.org',
    ),
);
