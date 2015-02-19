<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/marinpost/config.php';

require CRAFT_PLUGINS_PATH.'/marinpost/vendor/autoload.php';

class MarinPostVariable
{

    private $postObject;
    private $form;

    function __construct()
    {
        $s3 = \Aws\S3\S3Client::factory(
            array(
                'key' => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
                'region' => S3_REGION
            )
        );

        $this->s3Bucket = 'marinpost';

        $this->postObject = new \Aws\S3\Model\PostObject(
            $s3,
            S3_BUCKET,
            array(
                'acl' => 'public-read'
            )
        );

        $this->form = $this->postObject->prepareData()->getFormInputs();
    }

    /* Return AWS Access Key ID for direct upload to S3
     */
    public function awsAccessKey($optional = null)
    {
        return AWS_ACCESS_KEY_ID;
    }

    /* Return AWS S3 bucket for direct upload to S3
     */
    public function s3Bucket($optional = null)
    {
        return S3_BUCKET;
    }

    /* Return AWS S3 policy for direct upload to S3
     */
    public function s3Policy($optional = null)
    {
        return $this->form['policy'];
    }

    /* Return AWS signature for direct upload to S3
     */
    public function awsSignature($optional = null)
    {
        return $this->form['signature'];
    }

    // not currently used
    public function uniqid($optional = null)
    {
        return uniqid();
    }

    // debug only
    public function jsonS3Policy($optional = null)
    {
        return $this->postObject->getJsonPolicy();
    }

    /* Return virtual sub-directory on S3 for the current user
     */
    public function s3FolderNameForCurrentUser()
    {
        return craft()->userSession->isLoggedIn() ? craft()->userSession->id : null;
    }

    /* Return Asset folder for current user's virtual sub-directory on S3
     */
    public function s3FolderForCurrentUser($assetSourceId)
    {
        $folder = null;

        if (craft()->userSession->isLoggedIn()) {
            $folder = craft()->assets->findFolder([
                'sourceId' => $assetSourceId,
                'name' => $userId = craft()->userSession->id
            ]);
        }

        return $folder;
    }

    private function getIndexListForSource($sourceId)
    {
        $sessionId = craft()->assetIndexing->getIndexingSessionId();

        craft()->assetIndexing->getIndexListForSource($sessionId, $sourceId);

        return $sessionId;
    }

    private function getAssetIndexRecordByUri($sourceId, $sessionId, $uri)
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

    public function updateIndexForUri($sourceId, $uri)
    {
        $sessionId = $this->getIndexListForSource($sourceId);

        $record = $this->getAssetIndexRecordByUri($sourceId, $sessionId, $uri);

        if ($record)
        {
            $this->processIndexForSource($sessionId, $record->offset, $sourceId);
            return true;
        }

        return false;
    }
}
