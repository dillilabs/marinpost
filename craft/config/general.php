<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/general.php
 */

return array(
    '*' => array(
        'activateAccountFailurePath' => 'account/activation-failed',
        'activateAccountSuccessPath' => 'account/activation-success',
        'autoLoginAfterAccountActivation' => true,
        'invalidUserTokenPath' => 'account/invalid-token',
        'loginPath' => 'account/login',
        'logoutPath' => 'account/logout',
        'maxUploadFileSize' => 4194304,
        'omitScriptNameInUrls' => true,
        'postLoginRedirect' => 'submit',
        'setPasswordPath' => 'account/set-password',
        'setPasswordSuccessPath' => 'account/password-success',
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
