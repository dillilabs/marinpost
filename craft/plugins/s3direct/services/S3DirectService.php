<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/s3direct/vendor/autoload.php';

class S3DirectService extends BaseApplicationComponent
{
    private $plugin;

    function __construct()
    {
        $this->plugin = craft()->plugins->getPlugin('s3direct');
    }

    /**
     * Return form inputs required to upload a file direct to AWS S3:
     *
     *  - bucket
     *  - subfolder
     *  - policy
     *  - signature
     *  - access key id
     */
    public function s3UploadForm($assetSourceId)
    {
        $settings = $this->_assetSourceSettings($assetSourceId);
        $this->plugin->logger($settings);

        $s3 = \Aws\S3\S3Client::factory(
            array(
                'key' => $settings['keyId'],
                'secret' => $settings['secret'],
                'region' => $settings['location']
            )
        );

        // add Content-Type to default policy of:
        //
        // {"expiration":"2015-02-20T05:08:34Z","conditions":[{"bucket":"marinpost"},{"acl":"public-read"},["starts-with","$key",""]]}

        $postObject = new \Aws\S3\Model\PostObject(
            $s3,
            $settings['bucket'],
            array(
                'acl' => 'public-read',
                'policy_callback' => function($policy) {
                    array_push($policy['conditions'],
                        array('starts-with', '$Content-Type', '')
                    );
                    return $policy;
                }
            )
        );

        $form = $postObject->prepareData()->getFormInputs();

        return array(
            'bucket' => $settings['bucket'],
            'subfolder' => $settings['subfolder'],
            'policy' => $form['policy'],
            'signature' => $form['signature'],
            'keyId' => $settings['keyId'],
            'jsonPolicy' => $postObject->getJsonPolicy(),
            'sourceId' => $assetSourceId,
        );
    }

    /*
     * Return ID of currently logged in user
     */
    public function currentUserId()
    {
        return craft()->userSession->isLoggedIn() ? craft()->userSession->id : null;
    }

    /**
     * Return Asset sub folder corresponding to the current user's virtual sub-directory on S3
     */
    public function s3Folder($assetSourceId)
    {
        if ($userId = $this->currentUserId())
        {
            $name = $userId;

            return craft()->assets->findFolder([
                'sourceId' => $assetSourceId,
                'name' => $name
            ]);
        }

        return null;
    }

    /**
     * Update index and add required metadata for given Asset source
     * and array of uploaded files.
     */
    public function updateAssetIndexForFilenames($assetSourceId, $files = array())
    {
        $sessionId = $this->_getIndexListForSource($assetSourceId);
        $updated = 0;

        foreach ($files as $fileAttributes) {
            $uri = $this->_s3UriForFilename($fileAttributes['name']);
            $indexModel = $this->_getAssetIndexDataModelByUri($assetSourceId, $sessionId, $uri);

            if ($indexModel)
            {
                $assetId = $this->_updateAssetIndex($sessionId, $indexModel->offset, $assetSourceId);
                $this->_updateAssetMetadata($assetId, $fileAttributes);
                $updated += 1;
            }
        }

        return $updated;
    }

    /**
     * Return URL for Asset and optional transform.
     * If transform does not yet exist it is created.
     */
    public function getAssetUrl($assetId, $transform = null)
    {
        $saveSetting = craft()->config->get('generateTransformsBeforePageLoad');
        craft()->config->set('generateTransformsBeforePageLoad', true);

        $assetFile = craft()->assets->getFileById($assetId);
        $assetUrl = $assetFile->getUrl($transform);

        craft()->config->set('generateTransformsBeforePageLoad', $saveSetting);
        return $assetUrl;
    }

    //------------------
    // Private functions
    //------------------

    /**
     * Return asset (S3) source-specific settings.
     */
    private function _assetSourceSettings($assetSourceId)
    {
        return craft()->assetSources->getSourceById($assetSourceId)->settings;
    }

    /**
     * Prepare for indexing asset(s).
     */
    private function _getIndexListForSource($assetSourceId)
    {
        $sessionId = craft()->assetIndexing->getIndexingSessionId();
        craft()->assetIndexing->getIndexListForSource($sessionId, $assetSourceId);

        return $sessionId;
    }

    /**
     * Return S3 URI for provided filename.
     */
    private function _s3UriForFilename($filename)
    {
        $userId = $this->currentUserId();

        return "$userId/$filename";
    }

    /**
     * Return asset index data model for provided S3 URI.
     */
    private function _getAssetIndexDataModelByUri($assetSourceId, $sessionId, $uri)
    {
        $record = AssetIndexDataRecord::model()->findByAttributes(
            array(
                'sourceId' => $assetSourceId,
                'sessionId' => $sessionId,
                'uri' => $uri
            )
        );

        if ($record)
        {
            return AssetIndexDataModel::populateModel($record);
        }

        return false;
    }

    /**
     * Index the asset.
     */
    private function _updateAssetIndex($sessionId, $offset, $assetSourceId)
    {
        return craft()->assetIndexing->processIndexForSource($sessionId, $offset, $assetSourceId);
    }

    /**
     * Add or update Asset attributes:
     *
     *    title  -- for a Document asset
     *
     *    credit -- for an Image asset
     */
    private function _updateAssetMetadata($assetId, $attributes)
    {
        $setTitle = array_key_exists('title', $attributes) && !empty($attributes['title']);
        $setCredit = array_key_exists('credit', $attributes) && !empty($attributes['credit']);

        if ($setTitle or $setCredit)
        {
            $asset = craft()->assets->getFileById($assetId);
            $content = $asset->getContent();

            if ($setTitle)
            {
                $content->setAttribute('title', $attributes['title']);
            }

            if ($setCredit)
            {
                $content->setAttribute('credit', $attributes['credit']);
            }

            craft()->elements->saveElement($asset);

            return true;
        }

        return false;
    }
}
