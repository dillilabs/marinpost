<?php
namespace Craft;

class MarinPostVariable
{
    public function currentUserId()
    {
        return craft()->marinPost->currentUserId();
    }

    public function s3UploadForm($assetSourceId)
    {
        return craft()->marinPost->s3UploadForm($assetSourceId);
    }

    public function s3Folder($assetSourceId)
    {
        return craft()->marinPost->s3Folder($assetSourceId);
    }

    public function updateAssetIndexForFilenames($assetSourceId, $filenames = array())
    {
        return craft()->marinPost->updateAssetIndexForFilenames($assetSourceId, $filenames);
    }
}
