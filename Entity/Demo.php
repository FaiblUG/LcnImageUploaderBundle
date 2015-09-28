<?php
namespace Lcn\ImageUploaderBundle\Entity;

/**
 * Simple demo entity class
 *
 * For demonstration purposes only. In a real world scenario you might
 * want to use a Doctrine Entity or the like.
 *
 * @package Lcn\ImageUploaderBundle\Entity
 */
class Demo implements \Lcn\ImageUploaderBundle\Entity\ImageGallery {

    private $id;

    public function __construct($id) {
        $id = intval($id);

        if ($id < 100000000000) {
            throw new \Exception('invalid demo entity id:' .$id);
        }

        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    /**
     * Return the relative path to the directory
     * where the image uploads should be stored.
     *
     * The path should be relative to the directory defined
     * in parameter "lcn_file_uploader.file_base_path"
     *
     * @return String
     */
    public function getImageGalleryUploadPath() {
        $id = $this->getId();
        //include two characters of hash to avoid file system / operating system restrictions
        //with too many files/directories within a single directory.
        return 'demo-gallery-uploads/' . substr(md5($id), 0, 2) . '/' . $id;
    }
}