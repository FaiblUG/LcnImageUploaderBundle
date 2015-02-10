<?php
namespace Lcn\ImageUploaderBundle\Entity;

interface ImageGallery {

    /**
     * Return the relative path to the directory
     * where the image uploads should be stored.
     *
     * The path should be relative to the directory defined
     * in parameter "lcn_file_uploader.file_base_path"
     *
     * @return String e.g. user/{USER_ID}
     */
    public function getImageGalleryUploadPath();
}