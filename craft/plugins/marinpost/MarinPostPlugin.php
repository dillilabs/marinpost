<?php
namespace Craft;

class MarinPostPlugin extends BasePlugin
{
    /* Plugin initialization:
     *
     *  if saveUser event
     *      if ControlPanel
     *          include Javascript
     */
    public function init()
    {
        parent::init();

        $this->_onSaveUserEvent();

        if ($this->_isCp()) {
            $this->_includeJs();
        }
    }

    /* Hook to modify columns included in Control Panel entry list:
     *
     *  entry.dateCreated
     *
     *  entry.author
     *
     */
    public function modifyEntryTableAttributes(&$attributes, $source)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        $attributes['author'] = Craft::t('Author');
    }

    /* Hook to display of columns in Control Panel entry list:
     *
     *  entry.author
     *
     */
    public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
    {
        if (defined($attribute) && $attribute == 'author')
        {
            return $entry->author->name;
        }
    }

    /* Hook to modify sort by columns in Control Panel entry list:
     *
     *  entry.dateCreated
     *
     *  entry.author
     *
     */
    public function modifyEntrySortableAttributes(&$attributes)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        // $attributes['author'] = Craft::t('Author');
    }

    /* Private functions
     */

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
                // $this->_removeUserDashboardWidgets($user);
            }
        });
    }

    /* Keep {first,last}Name fields synchronized with name{First,Last} fields.
     *
     *  The former fields are:
     *
     *      "special"
     *      not required
     *      hidden in Account tab by JS
     *
     *  The latter fields are:
     *
     *      custom
     *      required
     *      editable in Profile tab
     */
    private function _syncUserName($user) {
        if (strcmp($user->firstName, $user->nameFirst) !== 0 || strcmp($user->lastName, $user->nameLast) !== 0) {
            $user->firstName = $user->nameFirst;
            $user->lastName = $user->nameLast;
            self::log("Synchronizing first and last name of {$user->name} ({$user->email})", LogLevel::Warning);
            craft()->users->saveUser($user);
        }
    }

    /* Clean up dashboard of non-admin User.
     */
    private function _removeUserDashboardWidgets($user) {
        foreach (craft()->dashboard->userWidgets as $widget) {
            if ($widget->type !== 'RecentEntries') {
                self::log("Deleting {$widget->type} widget from {$user->email} dashboard", LogLevel::Warning);
                craft()->dashboard->deleteUserWidgetById($widget->id);
            }
        }
    }

    /* Add Javascript to the Control Panel:
     *
     *  For all Users:
     *
     *      Remove First Name, Last Name and Week Start Day fields on user My Account Account tab
     *
     *  For non-admin Users:
     *
     *      Remove Photo and Bio fields on My Account Profile tab
     *
     *      Remove Dashboard settings link
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

    /* Boilerplate functions
     */

    public function getName()
    {
        return 'MarinPost';
    }

    public function getVersion()
    {
        return '0.0.10';
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
