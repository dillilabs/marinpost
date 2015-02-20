<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/marinpost/vendor/autoload.php';

class MarinPostService extends BaseApplicationComponent
{
    private $settings;
    private $s3PostObject;
    private $s3Form;

    function __construct()
    {
        $this->settings = craft()->plugins->getPlugin('marinpost')->getSettings();

        $s3 = \Aws\S3\S3Client::factory(
            array(
                'key' => $this->settings->awsAccessKeyId,
                'secret' => $this->settings->awsSecretAccessKey,
                'region' => $this->settings->s3Region
            )
        );

        // add Content-Type to default policy of:
        //
        // {"expiration":"2015-02-20T05:08:34Z","conditions":[{"bucket":"marinpost"},{"acl":"public-read"},["starts-with","$key",""]]}

        $this->s3PostObject = new \Aws\S3\Model\PostObject(
            $s3,
            $this->settings->s3Bucket,
            array(
                'acl' => 'public-read',
                'policy_callback' => function($policy) {
                        array_push($policy['conditions'], array('starts-with', '$Content-Type', ''));
                        return $policy;
                }
            )
        );

        $this->s3Form = $this->s3PostObject->prepareData()->getFormInputs();
    }

    public function awsAccessKeyId()
    {
        return $this->settings->awsAccessKeyId;
    }

    public function s3Bucket()
    {
        return $this->settings->s3Bucket;
    }

    public function s3Policy()
    {
        return $this->s3Form['policy'];
    }

    public function s3Signature()
    {
        return $this->s3Form['signature'];
    }

    // debug only
    public function s3PolicyJson()
    {
        return $this->s3PostObject->getJsonPolicy();
    }

    public function currentUserId()
    {
        return craft()->userSession->isLoggedIn() ? craft()->userSession->id : null;
    }

    /**
     * Return id of asset folder for current user's virtual sub-directory on S3
     */
    public function s3FolderId($sourceId)
    {
        if ($userId = $this->currentUserId())
        {
            return craft()->assets->findFolder([
                'sourceId' => $sourceId,
                'name' => $userId
            ]);
        }

        return null;
    }

    /**
     * Update Asset index for given source and array of filenames
     */
    public function updateAssetIndexForFilenames($sourceId, $filenames = array())
    {
        $userId = $this->currentUserId();

        $sessionId = $this->getIndexListForSource($sourceId);

        $updated = 0;

        foreach ($filenames as $filename) {
            $uri = $this->s3UriForFilename($filename);

            $model = $this->getAssetIndexDataModelByUri($sourceId, $sessionId, $uri);

            if ($model)
            {
                $this->processIndexForSource($sessionId, $model->offset, $sourceId);
                $updated += 1;
            }
        }

        return $updated;
    }

    //
    // Private functions
    //

    private function getIndexListForSource($sourceId)
    {
        $sessionId = craft()->assetIndexing->getIndexingSessionId();

        craft()->assetIndexing->getIndexListForSource($sessionId, $sourceId);

        return $sessionId;
    }

    private function s3UriForFilename($filename)
    {
        $userId = $this->currentUserId();

        return "$userId/$filename";
    }

    private function getAssetIndexDataModelByUri($sourceId, $sessionId, $uri)
    {
        $record = AssetIndexDataRecord::model()->findByAttributes(
            array(
                'sourceId' => $sourceId,
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

    private function processIndexForSource($sessionId, $offset, $sourceId)
    {
        craft()->assetIndexing->processIndexForSource($sessionId, $offset, $sourceId);
    }

}
