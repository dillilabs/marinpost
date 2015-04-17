<?php
namespace Craft;

class MpUserPlugin extends BasePlugin
{
    /**
     * Initialization:
     *
     *  Listen to users.onBeforeSaveUser event
     */
    public function init()
    {
        parent::init();

        $this->settings = $this->getSettings();

        $this->_onBeforeSaveUserEvent();
    }

    //----------------------
    // Event functions
    //----------------------

    /**
     * Respond to users.onBeforeSaveUser event.
     *
     *  If User firstName or lastName is blank:
     *
     *      Then add error(s) and prevent save.
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
                $valid= false;
            }

            if (empty(trim($lastName)))
            {
                $user->addError('lastName', 'Last name cannot be blank.');
                $valid= false;
            }

            if (!$valid)
            {
                $this->_log('Invalid user: '.$user->username);
                $event->performAction = false;
            }
        });
    }

    // ----------------
    // Helper functions
    // ----------------

    private function _log($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    // --------
    // Settings
    // --------

    protected function defineSettings()
    {
        return array(
            'forceLog' => array(AttributeType::Bool, 'default' => false),
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
        return '0.0.18';
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
