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

    /* Update index for S3 Asset source
     *
     * Need to do this asynchronously after one or more files are uploaded direct to S3.
     * Somehow...
     *
     * Would be good to only process index for new files. But how?
     * The offset (of the S3 assets) is via alphabetic sort order, rather than upload date.
     */
    public function updateAssetIndex($sourceId)
    {
        if (! craft()->userSession->isLoggedIn()) {
            return null;
        }

        $sessionId = craft()->assetIndexing->getIndexingSessionId();

        $indexList = craft()->assetIndexing->getIndexListForSource($sessionId, $sourceId);

        $processed = array();

        if (empty($indexList['error']))
        {
            for ($i = 0; $i < $indexList['total']; $i++)
            {
                $result = craft()->assetIndexing->processIndexForSource($sessionId, $i, $sourceId);
                array_push($processed, $result);
            }
        }

        return array('indexList' => $indexList, 'processed' => $processed);
    }
}
