<?php
namespace Craft;

class S3DirectController extends BaseController
{
    public function actionUpdateAssetsIndex()
    {
        $this->requirePostRequest();

        $this->requireAjaxRequest();

        $sourceId = craft()->request->getParam('sourceid');

        $fileNames = craft()->request->getParam('filenames');

        if ($sourceId && $fileNames)
        {
            $updated = craft()->s3Direct->updateAssetIndexForFilenames($sourceId, $fileNames);

            $folder = craft()->s3Direct->s3Folder($sourceId);

            $files = array();

            $criteria = array('folderId' => $folder->id);

            $assets = craft()->elements->getCriteria(ElementType::Asset, $criteria);

            foreach ($assets as $asset)
            {
                array_push($files, array(
                    'id' => $asset->id,
                    'url' => $asset->url,
                    'filename' => $asset->filename,
                    'size' => $asset->size,
                    'kind' => $asset->kind,
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
            $error = "Can't find no stinkin' sourceid or filenames...";

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
