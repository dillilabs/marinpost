<?php
namespace Craft;

require CRAFT_PLUGINS_PATH.'/marinpost/vendor/autoload.php';

use Aws\S3\S3Client;

class MarinPostVariable
{

    private $awsAccessKey;
    private $s3Bucket;
    private $postObject;
    private $form;

    function __construct()
    {
        $this->awsAccessKey = 'AKIAIKOBD2MRLLDVMXJQ';
        $AWSSECRET = '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';
        $AWSREGION = 'us-east-1';

        $s3 = S3Client::factory(
            array(
                'key' => $this->awsAccessKey,
                'secret' => $AWSSECRET,
                'region' => $AWSREGION
            )
        );

        $this->s3Bucket = 'marinpost';

        $this->postObject = new \Aws\S3\Model\PostObject(
            $s3,
            $this->s3Bucket,
            array(
                'acl' => 'public-read'
            )
        );

        $this->form = $this->postObject->prepareData()->getFormInputs();
    }

    public function awsAccessKey($optional = null)
    {
        return $this->awsAccessKey;
    }

    public function s3Bucket($optional = null)
    {
        return $this->s3Bucket;
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
