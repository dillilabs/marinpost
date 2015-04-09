<?php
namespace Craft;

class S3DirectController extends BaseController
{
    private $pluginSettings;

    function __construct()
    {
        $this->pluginSettings = craft()->plugins->getPlugin('s3direct')->getSettings();
    }

    public function actionUpdateAssetsIndex()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $sourceId = craft()->request->getParam('sourceid');
        $fileNames = craft()->request->getParam('filenames');
        $transform = $this->pluginSettings['imageTransform'];

        if ($sourceId && $fileNames)
        {
            $updated = craft()->s3Direct->updateAssetIndexForFilenames($sourceId, $fileNames);

            $folder = craft()->s3Direct->s3Folder($sourceId);
            $criteria = array('folderId' => $folder->id);
            $assets = craft()->elements->getCriteria(ElementType::Asset, $criteria);

            $files = array();
            foreach ($assets as $asset)
            {
                $url = $asset->kind == 'image' ? craft()->s3Direct->getAssetUrl($asset->id, $transform) : $asset->url;

                array_push($files, array(
                    'id' => $asset->id,
                    'filename' => $asset->filename,
                    'kind' => $asset->kind,
                    'size' => $asset->size,
                    'url' => $url,
                ));
            }

            $result = array(
                'filenames' => $fileNames,
                'updated' => $updated,
                'files' => $files,
                'sourceId' => $sourceId,
                'folderId' => $folder->id,
            );

            $this->returnJson($result);
        }
        else
        {
            $error = "No files found.";
            $this->returnErrorJson($error);
        }
    }

    public function actionGetAssetUrl()
    {
        $this->requireAjaxRequest();

        $assetId = craft()->request->getParam('assetId');
        $transform = craft()->request->getParam('transform');
        $url = craft()->s3Direct->getAssetUrl($assetId, $transform);

        $this->returnJson(array('url' => $url));
    }
}
