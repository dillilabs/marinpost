<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/s3direct/vendor/autoload.php';

class S3DirectService extends BaseApplicationComponent
{

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
        $settings = $this->assetSourceSettings($assetSourceId);
        S3DirectPlugin::log(print_r($settings, true), LogLevel::Info, true);

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
                        array_push($policy['conditions'], array('starts-with', '$Content-Type', ''));
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
     *
     * Return Asset sub folder corresponding to the current user's virtual sub-directory on S3
     *
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
     *
     * Update Asset index for given source and array of filenames
     *
     */
    public function updateAssetIndexForFilenames($assetSourceId, $filenames = array())
    {
        $userId = $this->currentUserId();

        $sessionId = $this->getIndexListForSource($assetSourceId);

        $settings = $this->assetSourceSettings($assetSourceId);

        $updated = 0;

        foreach ($filenames as $filename) {
            $uri = $this->s3UriForFilename($filename);

            $model = $this->getAssetIndexDataModelByUri($assetSourceId, $sessionId, $uri);

            if ($model)
            {
                $this->processIndexForSource($sessionId, $model->offset, $assetSourceId);
                $updated += 1;
            }
        }

        return $updated;
    }

    //
    // Private functions
    //

    private function assetSourceSettings($assetSourceId)
    {
        return craft()->assetSources->getSourceById($assetSourceId)->settings;
    }

    private function getIndexListForSource($assetSourceId)
    {
        $sessionId = craft()->assetIndexing->getIndexingSessionId();

        craft()->assetIndexing->getIndexListForSource($sessionId, $assetSourceId);

        return $sessionId;
    }

    private function s3UriForFilename($filename)
    {
        $userId = $this->currentUserId();

        return "$userId/$filename";
    }

    private function getAssetIndexDataModelByUri($assetSourceId, $sessionId, $uri)
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

    private function processIndexForSource($sessionId, $offset, $assetSourceId)
    {
        craft()->assetIndexing->processIndexForSource($sessionId, $offset, $assetSourceId);
    }

}
