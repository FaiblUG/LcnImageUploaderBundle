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

    public function getImage(ImageGallery $entity, $galleryName, $size, $filename) {
        $uploadPathSegment = $this->getUploadFolderName($entity, $galleryName);
        $sizesConfig = $this->getSizeConfig($galleryName, $size);
        $webPathPrefix = $this->fileUploader->getWebBasePath().'/'.$uploadPathSegment.'/'.$sizesConfig['folder'];

        return $webPathPrefix.'/'.$filename;
    }

    public function getGallery(ImageGallery $entity, $galleryName) {
        $images = $this->getImages($entity, $galleryName, 'large');
        $result = array();

        $sizeConfig = $this->getSizeConfig($galleryName, 'large');

        foreach ($images as $image) {
            $result[] = array(
                'src' => $image,
                'w' => $sizeConfig['max_width'],
                'h' => $sizeConfig['max_height'],
            );
        }

        return $result;
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
            'max_number_of_files' => $this->getMaxNumberOfFilesConfig($galleryName),
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

    public function getMaxNumberOfFilesConfig($galleryName) {
        return $this->getGalleryConfigValue($galleryName, 'max_number_of_files', 1);
    }

}