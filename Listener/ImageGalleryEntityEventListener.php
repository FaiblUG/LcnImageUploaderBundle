<?php
namespace Lcn\ImageUploaderBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Lcn\ImageUploaderBundle\Entity\ImageGallery;
use Lcn\ImageUploaderBundle\Service\ImageUploader;

class ImageGalleryEntityEventListener
{
    /**
     * @var ImageUploader
     */
    protected $imageUploader;

    public function __construct(ImageUploader $imageUploader) {
        $this->imageUploader = $imageUploader;
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof ImageGallery) {
            $this->imageUploader->removeAllUploads($entity);
        }
    }
}