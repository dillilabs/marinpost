<?php
namespace Craft;

class S3DirectController extends BaseController
{
    private $pluginSettings;

    function __construct()
    {
        $this->pluginSettings = craft()->plugins->getPlugin('s3direct')->getSettings();
    }

    /**
     * Update Assets index and add/update required metadata for uploaded file(s)
     * within given Asset source.
     */
    public function actionUpdateAssetsIndex()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $sourceId = craft()->request->getParam('sourceId');
        $inputFiles = craft()->request->getParam('files');
        $transform = craft()->request->getParam('imageTransform', $this->pluginSettings['defaultImageTransform']);

        if ($sourceId && $inputFiles)
        {
            $updated = craft()->s3Direct->updateAssetIndexForFilenames($sourceId, $inputFiles);

            $folder = craft()->s3Direct->s3Folder($sourceId);
            $criteria = array('folderId' => $folder->id, 'limit' => null);
            $assets = craft()->elements->getCriteria(ElementType::Asset, $criteria);

            $outputFiles = array();
            foreach ($assets as $asset)
            {
                $url = $asset->kind == 'image' ? craft()->s3Direct->getAssetUrl($asset->id, $transform) : $asset->url;

                array_push($outputFiles, array(
                    'id' => $asset->id,
                    'title' => $asset->title,
                    'filename' => $asset->filename,
                    'kind' => $asset->kind,
                    'size' => $asset->size,
                    'url' => $url,
                ));
            }

            $result = array(
                'files' => $inputFiles,
                'updated' => $updated,
                'files' => $outputFiles,
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

    /**
     * Return URL for Asset and optional transform.
     */
    public function actionGetAssetUrl()
    {
        $this->requireAjaxRequest();

        $assetId = craft()->request->getParam('assetId');
        $transform = craft()->request->getParam('transform');
        $url = craft()->s3Direct->getAssetUrl($assetId, $transform);

        $this->returnJson(array('url' => $url));
    }
}
