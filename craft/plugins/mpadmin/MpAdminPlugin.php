<?php
namespace Craft;

class MpAdminPlugin extends BasePlugin
{
    /**
     *  If CP request:
     *
     *      Inject Javascript to ensure only a single User group is selected per User.
     *
     *  Else:
     *
     *      Listen to onSaveEntry event.
     *
     *      Listen to onSaveUser event.
     */
    public function init()
    {
        parent::init();

        $this->settings = $this->getSettings();
        $this->_onSaveEntryEvent();

        if (craft()->request->isCpRequest())
        {
            $this->_ensureOneUserGroup();
            $this->_decorateEditAdView();
        }
        else
        {
            $this->_onSaveUserEvent();
        }
    }

    public function hasCpSection()
    {
        return true;
    }

    /**
     * Semi-smart logger.
     */
    public function logger($mixed, $level = LogLevel::Info)
    {
        self::log(is_array($mixed) ? json_encode($mixed) : $mixed, $level, $this->settings['forceLog']);
    }

    //----------------------
    // Entry table hooks
    //----------------------

    public function modifyEntryTableAttributes(&$attributes, $source)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        $attributes['author']      = Craft::t('Author');
        $attributes['email']       = Craft::t('Email');
    }

    public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
    {
        if (defined($attribute))
        {
            switch ($attribute)
            {
            case 'author':
                return $entry->author->name;
                break;

            case 'email':
                return $entry->email;
                break;
            }
        }
    }

    public function modifyEntrySortableAttributes(&$attributes)
    {
        $attributes['dateCreated'] = Craft::t('Created Date');
        $attributes['authorId']    = Craft::t('Author');
        $attributes['email']       = Craft::t('Email');
    }

    //----------------------
    // User table hooks
    //----------------------

    public function modifyUserTableAttributes(&$attributes, $source)
    {
        $attributes['city'] = Craft::t('City');
    }

    public function modifyUserSortableAttributes(&$attributes)
    {
        $attributes['city'] = Craft::t('City');
    }

    //----------------------
    // Event functions
    //----------------------

    /**
     * Respond to entries.onSaveEntry event.
     *
     *  If entry is published:
     *
     *      Notify the admin.
     *
     *      If entry section is ad, blog, letters, media, news or notices:
     *
     *          If content appears to contain offensive language:
     *
     *                  Notify the moderator.
     * 
     *  If ad entry is saved:
     *      Notify admin when submitted for review (not enabled and new)
     *      Notify user when approved (enabled and "Start Date" not set)
     *      Redirect Admin to Ad Matrix edit URL if "Start Date" is set
     */
    private function _onSaveEntryEvent()
    {
        craft()->on('entries.onSaveEntry', function(Event $event) {
            $entry = $event->params['entry'];

            // is entry save triggered from Control Panel (admin)
            if (craft()->request->isCpRequest()){
                // if an 'ad' entry
                if($entry->enabled && $entry->section->handle == 'ad'){
                    // was Start Date of ad entry not set, meaning this would
                    // be a response to an approval of ad
                    if($entry->adStartDate == NULL){
                        // notify user that his ad has been approved
                        craft()->mpAdmin->notifyUserOfAdApproval($entry);
                    } else { 
                        // this is a Save of ad Entry after Start date is saved
                        // redirect admin to Ad Matrix edit page so it can add
                        // the ad entry just saved.
                        $criteria = craft()->elements->getCriteria(ElementType::Entry);
                        $criteria->section = 'adMatrix';
                        $criteria->limit = 10;

                        // Get all entries that match
                        $entries = $criteria->find();

                        // Get the first entry that matches
                        $firstEntry = $criteria->first();

                        craft()->request->redirect($firstEntry->getCpEditUrl());
                    }
                } else if($entry->section->handle == 'adMatrix'){
                    // notify user that his ad has been published
                    if($entry->adEntries->last() != NULL)
                        craft()->mpAdmin->notifyUserOfAdPublish($entry);
                }
            } else {
                // save triggered programmatically not from CP

                // if a new ad, notify admin of the submitted ad for review
                if($entry->section->handle == 'ad' && $event->params['isNewEntry']){
                    craft()->mpAdmin->notifyAdminOfSubmittedAdEntry($entry);
                } else {
                    if ($entry->enabled)
                    {
                        
                        if($entry->section->handle == 'ad' && $entry->renewed == true){
                            // renewal case
                            craft()->mpAdmin->notifyAdminOfRenewedAdEntry($entry);
                        } else {
                            craft()->mpAdmin->notifyAdminOfPublishedEntry($entry);
                        }
        
                        $sections = array('ad', 'blog', 'letters', 'media', 'news', 'notices');
        
                        if (in_array($entry->section->handle, $sections))
                        {
                            $text = craft()->mpAdmin->entryText($entry);
        
                            if (craft()->mpAdmin->containsTriggerWords($text))
                            {
                                craft()->mpAdmin->notifyModerator($entry);
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Respond to users.onSaveUser event.
     *
     *  If user is a contributor:
     *
     *      If bio appears to contain offensive language:
     *
     *              Notify the moderator.
     */
    private function _onSaveUserEvent()
    {
        craft()->on('users.onSaveUser', function(Event $event) {
            $user = $event->params['user'];

            if ($user->isInGroup('contributor'))
            {
                $text = craft()->mpAdmin->userText($user);

                if (craft()->mpAdmin->containsTriggerWords($text))
                {
                    craft()->mpAdmin->notifyModerator($user);
                }
            }
        });
    }

    //----------------------
    // Private functions
    //----------------------

    /**
     * Inject Javascript into the Control Panel
     * to ensure that only one User Group is selected for a User.
     */
    private function _ensureOneUserGroup()
    {
        $js = <<<'JS'
var groups = $('form#userform input[type=checkbox][name="groups[]"]');

groups.click(function(e) {
    if (groups.filter(':checked').length > 1) {
        alert('Please select only one User Group.');

        return false;
    }
});
JS;
        craft()->templates->includeJs($js);
    }

    /**
     * Inject javascript in control panel on Edit Ad page to add instruction
     * and ad image.
     */
    private function _decorateEditAdView(){
        if (craft()->request->getSegment(2) == 'ad' && craft()->request->getSegment(3) != null) {

            // Now find the element, maybe with something like this:
            $id = explode('-', craft()->request->getSegment(3))[0];
            $entry = craft()->entries->getEntryById($id);                
            $siteMessages = craft()->globals->getSetByHandle('siteMessages');
            $instruction1 = $siteMessages->siteMessage->path('cp/ad/instruction1')->first()->text;
            $instruction2 = $siteMessages->siteMessage->path('cp/ad/instruction2')->first()->text;

            $adImageUrl = $entry->adImages->first()->url;
            $js0 = <<<JS
$('#title-field').prepend("<img width='320' height='232' src='{$adImageUrl}' />");
JS;
            $js1 = <<<JS
$('#title-field').prepend(`{$instruction1}`);
JS;
            $js2 = <<<JS
$('#title-field').prepend(`{$instruction2}`);
JS;
            // Inject JS into the the page
            if($entry->paid == 0){
                craft()->templates->includeJs($js1);
            } else {
                craft()->templates->includeJs($js2);
            }
            craft()->templates->includeJs($js0);
        } else if (craft()->request->getSegment(2) == 'adMatrix') {

            // Now find the element, maybe with something like this:
            $id = explode('-', craft()->request->getSegment(3))[0];
            $entry = craft()->entries->getEntryById($id);                
            $siteMessages = craft()->globals->getSetByHandle('siteMessages');
            $instructions = $siteMessages->siteMessage->path('cp/adMatrix/instruction')->first()->text;
            $js = <<<JS
$('#fields-adEntries-field').prepend(`{$instructions}`);
JS;
            craft()->templates->includeJs($js);
        }
    }
    //---------
    // Settings
    //---------

    protected function defineSettings()
    {
        return array(
            'adminEmail'     => array(AttributeType::String, 'default' => ''),
            'forceLog'       => array(AttributeType::Bool,   'default' => false),
            'moderatorEmail' => array(AttributeType::String, 'default' => ''),
            'triggerWords'   => array(AttributeType::String, 'default' => ''),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('mpadmin/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    //----------------------
    // Boilerplate functions
    //----------------------

    public function getName()
    {
        return 'Marin Post Admin';
    }

    public function getVersion()
    {
        return '1.3.1';
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
