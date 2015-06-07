<?php
namespace Craft;

class MpUserPlugin extends BasePlugin
{
    /**
     * Initialization:
     *
     *  Listen to users.onBeforeSaveUser event
     *
     *  IF not CP request:
     *
     *      Listen to users.onSaveUser event
     *      Listen to users.onSetPassword event
     */
    public function init()
    {
        parent::init();

        $this->settings = $this->getSettings();

        $this->_onBeforeSaveUserEvent();

        if (!craft()->request->isCpRequest())
        {
            $this->_onSaveUserEvent();
            $this->_onSetPasswordEvent();
        }
    }

    //----------------------
    // Event functions
    //----------------------

    /**
     * Respond to users.onBeforeSaveUser event.
     *
     *   If User firstName or lastName is blank:
     *
     *      Then add error(s) and prevent save.
     *
     *   If front-end request:
     *
     *      If is new User:
     *
     *          If honey pot field is populated:
     *
     *              Then prevent save.
     */
    private function _onBeforeSaveUserEvent()
    {
        craft()->on('users.onBeforeSaveUser', function(Event $event) {
            $user = $event->params['user'];

            $firstName = craft()->request->getPost('firstName', $user->firstName);
            $lastName = craft()->request->getPost('lastName', $user->lastName);

            $valid = true;

            if (empty(trim($firstName)))
            {
                $user->addError('firstName', 'First name cannot be blank.');
                $valid = false;
            }

            if (empty(trim($lastName)))
            {
                $user->addError('lastName', 'Last name cannot be blank.');
                $valid = false;
            }

            if (!craft()->request->isCpRequest() && $event->params['isNewUser'])
            {
                $honeypot = craft()->request->getPost($this->settings['honeypotField']);

                if (!empty($honeypot))
                {
                    $this->logger('User registration form submitted from IP='.craft()->request->ipAddress.', activated honeypot='.$honeypot, LogLevel::Warning);
                    // $user->addError($this->settings['honeypotField'], 'We think you might be a robot.');
                    $valid = false;
                }
            }

            if (!$valid)
            {
                $this->logger('Invalid user: '.$user->username);
                $event->performAction = false;
            }
        });
    }

    /**
     * Respond to users.onSaveUser event.
     *
     *  IF not CP request:
     *
     *      IF not new User:
     *
     *          IF "email" POST param exists:
     *
     *              Notify user.
     */
    private function _onSaveUserEvent()
    {
        craft()->on('users.onBeforeSaveUser', function(Event $event) {
            $user = $event->params['user'];
            $isNewUser = $event->params['isNewUser'];

            if (!$isNewUser)
            {
                $emailPostParam = craft()->request->getPost('email');

                if ($emailPostParam)
                {

                    $this->_notifyUser(
                        $user->email,
                        "Your Marin Post email address has been changed",
                        "The email address for your account on The Marin Post has been changed."
                    );

                    $this->logger("{$user->fullName} ({$user->email}) has changed their email address.", LogLevel::Warning);
                }
            }
        });
    }

    /**
     * Respond to users.onSetPassword event.
     *
     *  IF NOT CP request:
     *
     *      IF User status is active:
     *
     *          Notify User.
     */
    private function _onSetPasswordEvent()
    {
        craft()->on('users.onSetPassword', function(Event $event) {
            $user = $event->params['user'];

            if ($user->status != UserStatus::Active) {
              return;
            }

            $this->_notifyUser(
                $user->email,
                "Your Marin Post password has been changed",
                "The password for your account on The Marin Post has been changed.\n\nIf you did not do this, please contact us by replying to this email."
            );

            $this->logger("{$user->fullName} ({$user->email}) has changed their password.", LogLevel::Warning);
        });
    }

    /**
     * Semi-smart logger.
     */
    public function logger($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    // -----------------
    // Private functions
    // -----------------

    private function _notifyUser($to, $subject, $body)
    {
            $emailSettings = craft()->email->getSettings();

            $email = new EmailModel();
            $email->fromEmail = $emailSettings['emailAddress'];
            $email->replyTo   = $emailSettings['emailAddress'];
            $email->sender    = $emailSettings['emailAddress'];
            $email->fromName  = $emailSettings['senderName'];
            $email->toEmail   = $to;
            $email->subject   = $subject;
            $email->body      = $body;

            craft()->email->sendEmail($email);
    }

    // --------
    // Settings
    // --------

    protected function defineSettings()
    {
        return array(
            'honeypotField' => array(AttributeType::String, 'default' => 'birthdate'),
            'forceLog'      => array(AttributeType::Bool,   'default' => false),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('mpuser/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //----------------------
    // Boilerplate functions
    //----------------------

    public function getName()
    {
        return 'Marin Post Users';
    }

    public function getVersion()
    {
        return '0.0.25';
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
