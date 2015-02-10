<?php
namespace Lcn\ImageUploaderBundle\Twig;

use Lcn\ImageUploaderBundle\Entity\ImageGallery;
use Lcn\ImageUploaderBundle\Service\ImageUploader;

class ImageUploaderExtension extends \Twig_Extension
{
    /**
     * @var ImageUploader
     */
    private $imageUploader;

    public function __construct(ImageUploader $imageUploader) {
        $this->imageUploader = $imageUploader;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('lcn_get_upload_folder_name', array($this, 'getUploadFolderName')),
            new \Twig_SimpleFunction('lcn_get_image', array($this, 'getImage')),
            new \Twig_SimpleFunction('lcn_get_gallery_images', array($this, 'getGalleryImages')),
            new \Twig_SimpleFunction('lcn_get_first_gallery_image', array($this, 'getFirstGalleryImage')),
        );
    }

    public function getUploadFolderName(ImageGallery $entity, $galleryName)
    {
        return $this->imageUploader->getUploadFolderName($entity, $galleryName);
    }

    public function getImage(ImageGallery $entity, $galleryName, $size, $filename)
    {
        return $this->imageUploader->getImage($entity, $galleryName, $size, $filename);
    }

    public function getGalleryImages(ImageGallery $entity, $galleryName, $size, $limit = null)
    {
        return $this->imageUploader->getImages($entity, $galleryName, $size, $limit);
    }

    public function getFirstGalleryImage(ImageGallery $entity, $galleryName, $size)
    {
        return $this->imageUploader->getFirstImage($entity, $galleryName, $size);
    }

    public function getName()
    {
        return 'lcn_image_gallery_extension';
    }
}