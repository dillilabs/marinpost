<?php
namespace Craft;

class BusinessLogicPlugin extends BasePlugin
{
    /* On load
     */
    public function init()
    {
        parent::init();

        $this->_onSaveUserEvent();

        if ($this->_isCp()) {
            $this->_includeJs();
        }
    }

    /* Return true if control panel
     */
    private function _isCp() {
        return craft()->request->isCpRequest();
    }

    /* Return true if logged-in.
     */
    private function _isLoggedIn() {
        return craft()->userSession->isLoggedIn();
    }

    /* Return true if logged-in admin user.
     */
    private function _isAdmin() {
        return $this->_isLoggedIn() && craft()->userSession->user->admin;
    }

    /* Respond to the users.onSaveUser event.
     */
    private function _onSaveUserEvent()
    {
        craft()->on('users.onSaveUser', function(Event $event) {
            $user = $event->params['user'];

            $this->_syncUserName($user);

            if (! $user->admin) {
                $this->_removeUserDashboardWidgets($user);
            }
        });
    }

    /* Keep {first,last}Name fields synchronized with name{First,Last} fields.
     * The former fields are: "special", not required, hidden in Account tab by JS
     * The latter fields are: custom, required, editable in Profile tab
     */
    private function _syncUserName($user) {
        if (strcmp($user->firstName, $user->nameFirst) !== 0 || strcmp($user->lastName, $user->nameLast) !== 0) {
            $user->firstName = $user->nameFirst;
            $user->lastName = $user->nameLast;
            self::log("Synchronizing first and last name of {$user->name} ({$user->email})");
            craft()->users->saveUser($user);
        }
    }

    /* Clean up dashboard of non-admin User.
     */
    private function _removeUserDashboardWidgets($user) {
        foreach (craft()->dashboard->userWidgets as $widget) {
            if ($widget->type !== 'RecentEntries') {
                self::log("Deleting {$widget->type} widget from {$user->email} dashboard");
                craft()->dashboard->deleteUserWidgetById($widget->id);
            }
        }
    }

    /* Add Javascript to the CP
     *
     * For all Users:
     *
     *   Remove First Name, Last Name and Week Start Day fields on user My Account Account tab
     *
     * For non-admin Users:
     *
     *   Remove Photo and Bio fields on My Account Profile tab
     *
     *   Remove Dashboard settings link
     */
    private function _includeJs() {
        $js = <<<'JS'
var userFormFields = $('form#userform .field');
userFormFields.filter('#firstName-field, #lastName-field, #weekStartDay-field').remove();
JS;
        $non_admin_js = <<<'JS'
userFormFields.has('input#image-upload, textarea#fields-bio').remove();
$('#page-header #extra-headers').remove();
JS;
        if (! $this->_isAdmin()) {
            $js = $js . $non_admin_js;
        }

        craft()->templates->includeJs($js);
    }

    /* Hook to display entry.author in entry list
     */
    public function modifyEntryTableAttributes(&$attributes, $source)
    {
        $attributes['author'] = Craft::t('Author');
    }

    /* Hook to display entry.author.name in entry list
     */
    public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
    {
        if ($attribute == 'author')
        {
            return $entry->author->name;
        }
    }

    public function getName()
    {
        return 'Business Logic for '.craft()->getSiteName();
    }

    public function getVersion()
    {
        return '¯\_(ツ)_/¯';
    }

    public function getDeveloper()
    {
        return craft()->getSiteName();
    }

    public function getDeveloperUrl()
    {
        return 'https://github.com/lindseydiloreto/craft-businesslogic';
    }
}
