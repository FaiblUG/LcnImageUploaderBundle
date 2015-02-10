<?php

namespace Lcn\ImageUploaderBundle\Controller;

use Lcn\ImageUploaderBundle\Entity\Demo;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DemoController extends Controller
{

    /**
     * Create temporary Demo entity id and redirect to edit page
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $entityId = mt_rand(100000000000, 999999999999);

        return $this->redirect($this->generateUrl('lcn_image_uploader_demo_edit', array('entityId' => $entityId)));
    }

    /**
     * Show Uploads for the given entity id.
     *
     * In a real world scenario you might want to check view permissions
     *
     * @param Request $request
     * @param $entityId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request, $entityId)
    {
        $entity = new Demo($entityId); //in a real world scenario you would retrieve the entity from a repository.
        $galleryName = 'demo'; //the galleryName has to match a defined gallery in parameter "lcn.image_uploader.galleries"

        return $this->render('LcnImageUploaderBundle:Demo:show.html.twig', array(
          'entity' => $entity,
          'galleryName' => $galleryName,
        ));
    }

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

}

