<?php

namespace Lcn\ImageUploaderBundle\Service;

use Lcn\ImageUploaderBundle\Entity\ImageGallery;
use Lcn\FileUploaderBundle\Services\FileUploader;

class ImageUploader
{

    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var array
     */
    private $galleries;

    /**
     * @var array
     */
    private $allowedExtensions;



    public function __construct(FileUploader $fileUploader, array $galleries, array $allowedExtensions)
    {
        $this->fileUploader = $fileUploader;
        $this->galleries = $galleries;
        $this->allowedExtensions = $allowedExtensions;

        return $this;
    }

    public function getFirstImage(ImageGallery $entity, $galleryName, $size) {
        $images = $this->getImages($entity, $galleryName, $size, 1);
        if (!empty($images)) {
            return $images[0];
        }
    }

    public function getImages(ImageGallery $entity, $galleryName, $size, $limit = null) {
        if (!array_key_exists($size, $this->galleries[$galleryName]['sizes'])) {
            throw new \InvalidArgumentException('Image size does not exist: '.$size);
        }

        $uploadPath = $this->getUploadFolderName($entity, $galleryName);
        $filenames = $this->fileUploader->getFilenames($uploadPath);

        $result = array();
        foreach ($filenames as $index => $filename) {
            $result[] = $this->getImage($entity, $galleryName, $size, $filename);
            if ($limit && $limit === $index + 1) {
                break;
            }
        }

        return $result;
    }

    public function getImageFilenames(ImageGallery $entity, $galleryName, $limit = null) {
        $uploadPath = $this->getUploadFolderName($entity, $galleryName);

        return $this->fileUploader->getFilenames($uploadPath);
    }

    /**
     * Get Image url for given parameters.
     * This method does not require any file system calls and is therefore
     * quite fast. YOu prefer this method to getImages if you already know the filename
     *
     * @param ImageGallery $entity
     * @param $galleryName
     * @param $size
     * @param $filename
     *
     * @return string
     */
    public function getImage(ImageGallery $entity, $galleryName, $size, $filename) {
        return $this->getWebPathPrefix($entity, $galleryName, $size).'/'.$filename;
    }

    private function getWebPathPrefix(ImageGallery $entity, $galleryName, $size) {
        $uploadPathSegment = $this->getUploadFolderName($entity, $galleryName);
        $sizesConfig = $this->getSizeConfig($galleryName, $size);

        return $this->fileUploader->getWebBasePath().'/'.$uploadPathSegment.'/'.$sizesConfig['folder'];
    }

    private function getFilePathPrefix(ImageGallery $entity, $galleryName, $size) {
        $uploadPathSegment = $this->getUploadFolderName($entity, $galleryName);
        $sizesConfig = $this->getSizeConfig($galleryName, $size);

        return $this->fileUploader->getFileBasePath().'/'.$uploadPathSegment.'/'.$sizesConfig['folder'];
    }

    public function getMaxNumberOfImages($galleryName) {
        return $this->getGalleryConfigValue($galleryName, 'max_number_of_files', 1);
    }

    public function getGallery(ImageGallery $entity, $galleryName, $size = 'large') {
        $result = array();

        $imageFilenames = $this->getImageFilenames($entity, $galleryName);

        $sizeConfig = $this->getSizeConfig($galleryName, $size);

        $webPathPrefix = $this->getWebPathPrefix($entity, $galleryName, $size);
        $filePathPrefix = $this->getFilePathPrefix($entity, $galleryName, $size);

        foreach ($imageFilenames as $imageFilename) {
            $size = $this->getImageSize($filePathPrefix.'/'.$imageFilename);
            $result[] = array(
              'src' => $webPathPrefix.'/'.$imageFilename,
              'w' => $size['width'],
              'h' => $size['height'],
              'max_w' => $sizeConfig['max_width'],
              'max_h' => $sizeConfig['max_height'],
            );
        }

        return $result;
    }

    private function getImageSize($filepath) {
        if (file_exists($filepath)) {
            $imagesize = getimagesize($filepath);

            return array(
              'width' => $imagesize[0],
              'height' => $imagesize[1]
            );
        }
        else {
            return array('width' => 0, 'height' => 0);
        }
    }

    public function syncFromTemp(ImageGallery $entity, $galleryName) {
        $this->fileUploader->syncFilesFromTemp($this->getUploadFolderName($entity, $galleryName));
    }

    public function syncToTemp(ImageGallery $entity, $galleryName) {
        $this->fileUploader->syncFilesToTemp($this->getUploadFolderName($entity, $galleryName));
    }

    public function handleFileUpload(ImageGallery $entity, $galleryName)
    {
        $this->fileUploader->handleFileUpload(array(
          'folder' => $this->getUploadFolderName($entity, $galleryName),
          'sizes' => $this->getSizesConfig($galleryName),
          'max_number_of_files' => $this->getMaxNumberOfImages($galleryName),
          'allowed_extensions' => $this->allowedExtensions,
        ));
    }

    public function removeAllUploads(ImageGallery $entity) {
        $this->fileUploader->removeFiles($entity->getImageGalleryUploadPath());
    }

    public function getUploadFolderName(ImageGallery $entity, $galleryName) {
        return $entity->getImageGalleryUploadPath().'/'.$galleryName;
    }

    private function getGalleryConfig($galleryName) {
        if (!array_key_exists($galleryName, $this->galleries)) {
            throw new \InvalidArgumentException('Gallery does not exist: '.$galleryName);
        }

        return $this->galleries[$galleryName];
    }

    private function getGalleryConfigValue($galleryName, $key, $defaultValue = null) {
        $galleryConfig = $this->getGalleryConfig($galleryName);

        return array_key_exists($key, $galleryConfig) ? $galleryConfig[$key] : $defaultValue;
    }

    private function getSizesConfig($galleryName) {
        return $this->getGalleryConfigValue($galleryName, 'sizes', array());
    }

    public function getSizeConfig($galleryName, $size) {
        $sizesConfig = $this->getSizesConfig($galleryName);

        if (!array_key_exists($size, $sizesConfig)) {
            throw new \InvalidArgumentException('Size ' . $size . ' does not exist for gallery '.$galleryName);
        }

        return $sizesConfig[$size];
    }

}