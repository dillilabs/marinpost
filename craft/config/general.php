<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/general.php
 */

return array(
    '*' => array(
        'activateAccountFailurePath' => 'account/activate/error',
        'activateAccountSuccessPath' => 'account/activate/welcome',
        'autoLoginAfterAccountActivation' => true,
        'invalidUserTokenPath' => 'account/token/invalid',
        'loginPath' => 'account/login',
        'logoutPath' => 'account/logout',
        'maxUploadFileSize' => 4194304,
        'omitScriptNameInUrls' => true,
        'postLoginRedirect' => 'submit',
        'setPasswordPath' => 'account/password/set',
        'setPasswordSuccessPath' => 'account/password/updated',
        'siteName' => 'The Marin Post',
        'useEmailAsUsername' => true,
    ),
    'dev' => array(
        'devMode' => true,
        'siteUrl' => 'http://dev.marinpost.org',
        'testToEmailAddress' => 'stvpedersen@gmail.com',
    ),
    'live' => array(
        'devMode' => true,
        'siteUrl' => 'http://marinpost.org',
        'testToEmailAddress' => 'stvpedersen@gmail.com',
    ),
);
