<?php
namespace Craft;

class MarinPostVariable
{

    public function awsAccessKeyId()
    {
        return craft()->marinPost->awsAccessKeyId();
    }

    public function s3Bucket()
    {
        return craft()->marinPost->s3Bucket();
    }

    public function s3Policy()
    {
        return craft()->marinPost->s3Policy();
    }

    public function s3Signature()
    {
        return craft()->marinPost->s3Signature();
    }

    public function s3PolicyJson()
    {
        return craft()->marinPost->s3PolicyJson();
    }

    public function s3FolderId($sourceId)
    {
        return craft()->marinPost->s3FolderId($sourceId);
    }

    public function updateAssetIndexForFilenames($sourceId, $filenames = array())
    {
        return craft()->marinPost->updateAssetIndexForFilenames($sourceId, $filenames);
    }

    public function currentUserId()
    {
        return craft()->marinPost->currentUserId();
    }
}
