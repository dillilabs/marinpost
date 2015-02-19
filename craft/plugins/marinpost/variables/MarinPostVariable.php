<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/marinpost/config.php';

require CRAFT_PLUGINS_PATH.'/marinpost/vendor/autoload.php';

class MarinPostVariable
{

    private $s3PostObject;
    private $s3Form;

    function __construct()
    {
        $s3 = \Aws\S3\S3Client::factory(
            array(
                'key' => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
                'region' => S3_REGION
            )
        );

        $this->s3PostObject = new \Aws\S3\Model\PostObject(
            $s3,
            S3_BUCKET,
            array(
                'acl' => 'public-read'
            )
        );

        $this->s3Form = $this->s3PostObject->prepareData()->getFormInputs();
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
        return $this->s3Form['policy'];
    }

    /* Return AWS signature for direct upload to S3
     */
    public function awsSignature($optional = null)
    {
        return $this->s3Form['signature'];
    }

    // not currently used
    public function uniqid($optional = null)
    {
        return uniqid();
    }

    // debug only
    public function jsonS3Policy($optional = null)
    {
        return $this->s3PostObject->getJsonPolicy();
    }

    /* Return id of current user
     */
    public function currentUserId()
    {
        return craft()->userSession->isLoggedIn() ? craft()->userSession->id : null;
    }

    /* Return id of asset folder for current user's virtual sub-directory on S3
     */
    public function s3FolderId($assetSourceId)
    {
        if ($userId = $this->currentUserId())
        {
            return craft()->assets->findFolder([
                'sourceId' => $assetSourceId,
                'name' => $userId
            ]);
        }

        return null;
    }

    /* Update Asset index for given source and array of filenames
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

    /* 
     * Private functions
     */

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
