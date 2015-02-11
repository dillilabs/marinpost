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

    public function awsAccessKey($optional = null)
    {
        return AWS_ACCESS_KEY_ID;
    }

    public function s3Bucket($optional = null)
    {
        return S3_BUCKET;
    }

    public function s3Policy($optional = null)
    {
        return $this->form['policy'];
    }

    // debug
    public function jsonS3Policy($optional = null) {
        return $this->postObject->getJsonPolicy();
    }

    public function awsSignature($optional = null)
    {
        return $this->form['signature'];
    }

    public function uniqid($optional = null)
    {
        return uniqid();
    }
}
