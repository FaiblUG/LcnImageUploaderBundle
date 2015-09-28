<?php

namespace Lcn\ImageUploaderBundle\Service;

use Lcn\ImageUploaderBundle\Entity\ImageGallery;
use Lcn\FileUploaderBundle\Services\FileUploader;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImageUploader
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var FileUploader[]
     */
    private $fileUploaders = array();

    /**
     * @var array
     */
    private $galleries;

    /**
     * @var array
     */
    private $allowedExtensions;



    public function __construct(ContainerInterface $container, array $galleries, array $allowedExtensions)
    {
        $this->container = $container;
//        $this->fileUploader = $container->get('lcn.file_uploader');
        $this->galleries = $galleries;
        $this->allowedExtensions = $allowedExtensions;

        return $this;
    }

    public function countImages(ImageGallery $entity, $galleryName)
    {
        $uploadPath = $this->getUploadFolderName($entity, $galleryName);

        return count($this->getFileUploader($galleryName)->getFilenames($uploadPath));
    }

    public function countTempImages(ImageGallery $entity, $galleryName)
    {
        $uploadPath = $this->getUploadFolderName($entity, $galleryName);

        return count($this->getFileUploader($galleryName)->getTempFiles($uploadPath));
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
        $filenames = $this->getFileUploader($galleryName)->getFilenames($uploadPath);

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

        return $this->getFileUploader($galleryName)->getFilenames($uploadPath);
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
        return $this->getFileUploader($galleryName)->getFileUrlForPrefix(
          $this->getWebPathPrefix($entity, $galleryName),
          $filename,
          $size
        );
    }

    private function getWebPathPrefix(ImageGallery $entity, $galleryName) {
        $uploadPathSegment = $this->getUploadFolderName($entity, $galleryName);

        return $this->getFileUploader($galleryName)->getWebBasePath().'/'.$uploadPathSegment;
    }

    private function getFilePathPrefix(ImageGallery $entity, $galleryName, $size) {
        $uploadPathSegment = $this->getUploadFolderName($entity, $galleryName);
        $sizesConfig = $this->getSizeConfig($galleryName, $size);

        return $this->getFileUploader($galleryName)->getFileBasePath().'/'.$uploadPathSegment.'/'.$sizesConfig['folder'];
    }

    public function getMaxNumberOfImages($galleryName) {
        return $this->getGalleryConfigValue($galleryName, 'max_number_of_files', 1);
    }

    public function getMaxFileSize($galleryName) {
        return $this->getGalleryConfigValue($galleryName, 'max_file_size', null);
    }

    public function getGallery(ImageGallery $entity, $galleryName, $size = 'large') {
        $result = array();

        $imageFilenames = $this->getImageFilenames($entity, $galleryName);

        $sizeConfig = $this->getSizeConfig($galleryName, $size);

        foreach ($imageFilenames as $imageFilename) {
            $imageUrl = $this->getImage($entity, $galleryName, $size, $imageFilename);
            $size = $this->getImageSize($imageUrl);
            $result[] = array(
              'src' => $imageUrl,
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
        $this->getFileUploader($galleryName)->syncFilesFromTemp($this->getUploadFolderName($entity, $galleryName));
    }

    public function syncToTemp(ImageGallery $entity, $galleryName) {
        $this->getFileUploader($galleryName)->syncFilesToTemp($this->getUploadFolderName($entity, $galleryName));
    }

    public function handleFileUpload(ImageGallery $entity, $galleryName)
    {
        $this->getFileUploader($galleryName)->handleFileUpload(array(
          'folder' => $this->getUploadFolderName($entity, $galleryName),
        ));
    }

    public function removeAllUploads(ImageGallery $entity) {
        $this->container->get('lcn.file_uploader')->removeFiles($entity->getImageGalleryUploadPath());
    }

    public function getUploadFolderName(ImageGallery $entity, $galleryName) {
        return $entity->getImageGalleryUploadPath().'/'.$galleryName;
    }

    protected function getFileUploader($galleryName) {
        if (!array_key_exists($galleryName, $this->fileUploaders)) {
            $fileUploader = $this->container->get('lcn.file_uploader');
            $fileUploader->setOption('sizes', $this->getSizesConfig($galleryName));
            $fileUploader->setOption('max_number_of_files', $this->getMaxNumberOfImages($galleryName));
            $fileUploader->setOption('max_file_size', $this->getMaxFileSize($galleryName));
            $fileUploader->setOption('allowed_extensions', $this->allowedExtensions);

            $this->fileUploaders[$galleryName] = $fileUploader;
        }

        return $this->fileUploaders[$galleryName];
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