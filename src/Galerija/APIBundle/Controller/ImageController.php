<?php

namespace Galerija\APIBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Galerija\APIBundle\Entity\Image;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

class ImageController extends FOSRestController implements ClassResourceInterface
{
    public function putAction(File $file)
    {

    }

    /**
     * Create an Image from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new image from the submitted data.",
     *   statusCodes = {
     *     200 = "Returned when successful.",
     *     400 = "Returned when image wasnt saved successfully."
     *   }
     * )
     *
     * @param ParamFetcher $paramFetcher Paramfetcher
     *
     * @RequestParam(name="title", nullable=false, strict=true, description="Title.")
     * @RequestParam(name="data", nullable=false, strict=true, description="Image data.")
     *
     * @return View
     */
    public function postAction(Request $request, ParamFetcher $paramFetcher)
    {
        $image = new Image();

        $imageFile = base64_decode($paramFetcher->get('data'));

        $image->setImagePath('images/'. uniqid() . '.png');
        $image->setTitle($paramFetcher->get('title'));

        $imageSaveSuccess = file_put_contents($image->getImagePath(), $imageFile);
        $view = View::create();
        return $imageSaveSuccess ? $view->setData($image)->setStatusCode(200) : $view->setStatusCode(400);
    }

    public function getAction()
    {

    }

    public function getAllAction()
    {

    }

    public function deleteAction()
    {

    }

    public function patchAction()
    {

    }

}
