<?php
namespace Craft;

class S3DirectVariable
{
    public function currentUserId()
    {
        return craft()->s3Direct->currentUserId();
    }

    public function s3UploadForm($assetSourceId)
    {
        return craft()->s3Direct->s3UploadForm($assetSourceId);
    }

    public function s3Folder($assetSourceId)
    {
        return craft()->s3Direct->s3Folder($assetSourceId);
    }

    public function updateAssetIndexForFilenames($assetSourceId, $filenames = array())
    {
        return craft()->s3Direct->updateAssetIndexForFilenames($assetSourceId, $filenames);
    }

    /**
     * TODO remove
     */
    public function updateAssetContent($assetId, $key, $value)
    {
        $asset = craft()->assets->getFileById($assetId);
        $content = $asset->getContent();
        $content->setAttribute($key, $value);
        craft()->elements->saveElement($asset);
    }
}
