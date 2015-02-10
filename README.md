LcnFileUploaderBundle
========================

Easy ajax image file uploads for Symfony 2. MIT License.
Built upon [LcnFileUploaderBundle](https://github.com/FaiblUG/LcnFileUploaderBundle).


Introduction
------------

This bundle provides enhanced image upload widgets based on [LcnFileUploaderBundle](https://github.com/FaiblUG/LcnFileUploaderBundle). Both drag and drop and multiple file selection are fully supported in compatible browsers.

The uploader delivers files to a folder that you specify. If that folder already contains files, they are displayed side by side with new files, as existing files that can be removed.

The bundle can automatically scale images to sizes you specify. The provided synchronization methods make it possible to create forms in which attached files respect "save" and "cancel" operations.

If need to handle image uploads only, you should check out [LcnImageUploaderBundle](https://github.com/FaiblUG/LcnImageUploaderBundle) which extends LcnFileUploaderBundle.


Installation
------------

### Step 1: Install dependencies

#### LcnFileUploaderBundle

Install the required [LcnFileUploaderBundle](https://github.com/FaiblUG/LcnFileUploaderBundle).

#### jQuery

Make sure that [jQuery](http://jquery.com/) is included in your html document:

```html
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
```

#### Underscore.js

Make sure that [Underscore.js](http://underscorejs.org/) is included in your html document

```html
<script src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.7.0/underscore-min.js"></script>
```


### Step 2: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require locaine/lcn-image-uploader-bundle "~1.0"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 3: Enable the Bundle

Then, enable the bundle by adding the following line in the `app/AppKernel.php`
file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Lcn\ImageUploaderBundle\LcnImageUploaderBundle(),
        );

        // ...
    }

    // ...
}
```

Usage
-----

### Add/Edit/Remove uploads

#### Entity Code

If you need to upload files to not yet persisted entities (during creation) then you need to 
deal with temporary editIds which makes things a little bit more complicated.

In this example, we assume that you want to attach one or more uploaded image files to an existing entity (Demo).

This entity has to implement the ImageGallery interface.


Example:

```php
class Demo implements \Lcn\ImageUploaderBundle\Entity\ImageGallery {
    
    ...
    
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
        return 'demo-gallery/'.$this->getId();
    }
}
```

#### Controller Code

***Fetching $entity and validating that the user is allowed to edit that particular entity is up to you.***


```php  
<?php

namespace Lcn\ImageUploaderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DemoController extends Controller
{
    ...
    
    
    /**
     * Edit Uploads for the given entity id or create new entity with uploads.
     *
     * In a real world scenario you might want to check edit permissions
     *
     * @param Request $request
     * @param $entityId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $entityId)
    {
        $entity = new Demo($entityId); //in a real world scenario you would retrieve the entity from a repository.
        $galleryName = 'demo'; //the galleryName has to match a defined gallery in parameter "lcn.image_uploader.galleries"

        $imageUploader = $this->get('lcn.image_uploader');

        $form = $this->createFormBuilder()
          ->setAction($this->generateUrl('lcn_image_uploader_demo_edit', array('entityId'  => $entity->getId())))
          ->setMethod('POST')
          ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $imageUploader->syncFromTemp($entity, $galleryName);

                return $this->redirect($this->generateUrl('lcn_image_uploader_demo_show', array('entityId'  => $entity->getId())));
            }
        } else {
            $imageUploader->syncToTemp($entity, $galleryName);
        }

        return $this->render('LcnImageUploaderBundle:Demo:edit.html.twig', array(
          'entity' => $entity,
          'galleryName' => $galleryName,
          'uploadUrl' => $this->generateUrl('lcn_image_uploader_demo_handle_file_upload', array('entityId' => $entity->getId())),
          'form' => $form->createView(),
        ));
    }

    /**
     * Store the uploaded file.
     *
     * In a real world scenario you might probably want to check
     * if the user is allowed to store uploads for the given entity id.
     *
     * Delegates to LcnImageUploader which implements a REST Interface and handles file uploads as well as file deletions.
     *
     * This action must not return a response. The response is generated in native PHP by LcnFileUploader.
     *
     * @param Request $request
     * @param int $userId
     */
    public function handleFileUploadAction(Request $request, $entityId)
    {
        $entity = new Demo($entityId); //in a real world scenario you would retrieve the entity from a repository.
                $galleryName = 'demo'; //the galleryName has to match a defined gallery in parameter "lcn.image_uploader.galleries"

        $this->get('lcn.image_uploader')->handleFileUpload($entity, $galleryName);
    }
    
    ...
}
```

#### In Your Layout

***You can skip this step if you are using [LcnIncludeAssetsBundle](https://github.com/FaiblUG/LcnIncludeAssetsBundle).***


Include these stylesheets and scripts in your html document:

```html
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/main.css') }}">
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/theme.css') }}">
    <script src="{{ asset('bundles/lcnfileuploader/dist/main.js') }}"></script>
```
    
Or you can use assetic in your twig template:

```twig
{% extends 'base.html.twig' %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/main.css') }}">
    <link rel="stylesheet" href="{{ asset('bundles/lcnfileuploader/dist/theme.css') }}">
{% endblock %}
{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('bundles/lcnfileuploader/dist/main.js') }}"></script>
{% endblock %}
```

The exact position and order does not matter. However, for best performance you should include the link tags in your head section and the script tag right before the closing body tag.

#### In the Edit Template
====================

Now include the upload widget anywhere on your page:

```twig
    {% include 'LcnFileUploaderBundle:Theme:lcnFileUploaderWidget.html.twig' with {
        'uploadUrl': uploadUrl,
        'uploadFolderName': lcn_get_upload_folder_name(entity, galleryName),
        'formSelector': '#lcn-image-uploader-demo'
    } %}
```
    
Full example:

```twig
{{ form_start(form, { 'attr': { 'id': 'lcn-image-uploader-demo' } }) }}

    {{ form_errors(form) }}

    {% include 'LcnFileUploaderBundle:Theme:lcnFileUploaderWidget.html.twig' with {
        'uploadUrl': uploadUrl,
        'uploadFolderName': lcn_get_upload_folder_name(entity, galleryName),
        'formSelector': '#lcn-image-uploader-demo'
    } %}

    {{ form_rest(form) }}
</form>
```


### Retrieving existing Uploads

#### Retrieve Thumbnail URLs in Controller ####

If you are dealing with image uploads, you can pass a defined size name:

```php
$imageUploader = $this->container->get('lcn.image_uploader');
$imageUrls = $imageUploader->getImages($entity, $galleryName, $size = 'thumbnail');
```

The image sizes are defined per gallery in lcn.image_uploader.galleries parameter:

```yaml
  lcn.image_uploader.galleries:
    # define your own named galleries here
    # The following "demo" gallery is just an example.
    demo: #this is the gallery name
      max_number_of_files: 5
      sizes:
        # required: "thumbnail"
        thumbnail:
          folder: thumbnail
          max_width: 200
          max_height: 150
          crop: true
        # optional: "original" - define original image size if you want to restrict the maximum image dimensions:
        original:
          folder: original
          max_width: 2000
          max_height: 1000
          crop: false
        # optional: define any additional image size you need.
        # For more advanced image resizing options you might also want to use specialized
        # bundles for that, e.g. https://github.com/liip/LiipImagineBundle.
        standard:
          folder: standard
          max_width: 600
          max_height: 400
          crop: true
```

#### Retrieve Thumbnail URLs in Twig Template ####
```twig
lcn_get_gallery_images(entity, galleryName, 'thumbnail')
lcn_get_gallery_images(entity, galleryName, 'standard')
lcn_get_gallery_images(entity, galleryName, 'original')
```

You can also pass a limit parameter (5 in this example):

```twig
lcn_get_gallery_images(entity, galleryName, 'thumbnail', 5)

```

If you only need the first thumbnail url, you can get it like this:
```twig
lcn_get_first_gallery_image(entity, galleryName, 'thumbnail')
```

If you know the file name (e.g. stored as property on your entity) you can explicitly get the corresponding thumbnail:
```twig
lcn_get_image(entity, galleryName, 'thumbnail', filename)
```

#### Removing Files

When an entity that implements the ImageGallery interface gets deleted, the ImageGalleryEntityEventListener takes care of deleting all corresponding image uploads.

### Removing Temporary Files

You should make sure that the temporary files do not eat up your storage.

The following Command Removes all temporary uploads older than 120 minutes

```sh
app/console lcn:file-uploader:cleanup --min-age-in-minutes=120
´´´

You might want to setup a cronjob that automatically executes that command in a given interval.


### More Options

As this bundle builds upon [LcnFileUploaderBundle](https://github.com/FaiblUG/LcnFileUploaderBundle) it is worth reading the bundles documentation for more advanced options. 


Limitations
===========

This bundle accesses the file system via the `glob()` function. It won't work out of the box with an S3 stream wrapper.


