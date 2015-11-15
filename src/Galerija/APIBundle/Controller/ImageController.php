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

class ImageController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Put an Image from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new image from the submitted data.",
     *   statusCodes = {
     *     200 = "Returned when successful.",
     *     404 = "Returned when image wasnt found.",
     *     400 = "Returned when image wasnt saved successfully."
     *   }
     * )
     *
     * @param ParamFetcher $paramFetcher Paramfetcher
     * @param string $slug
     *
     * @QueryParam(name="title", nullable=false, strict=true, description="Title.")
     * @QueryParam(name="data", nullable=false, strict=true, description="Image data.")
     *
     * @return View
     */
    public function putAction(ParamFetcher $paramFetcher, $slug)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $view = View::create();

        $image = $em->getRepository("GalerijaAPIBundle:Image")->find($slug);
        if(!$image) {
            return  $view->setStatusCode(404);
        }

        $lastImage = clone $image;

        $imageFile = base64_decode($paramFetcher->get('data'));

        $image->setImagePath('images/'. uniqid() . '.png');
        $image->setTitle($paramFetcher->get('title'));


        $imageSaveSuccess = file_put_contents($image->getImagePath(), $imageFile);

        if($imageSaveSuccess) {
            unlink($lastImage->getImagePath());
            $em->persist($image);
            $em->flush();
        }

        return $imageSaveSuccess ? $view->setData($image)->setStatusCode(200) : $view->setStatusCode(400);
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
    public function postAction(ParamFetcher $paramFetcher)
    {
        $image = new Image();

        $imageFile = base64_decode($paramFetcher->get('data'));

        $image->setImagePath('images/'. uniqid() . '.png');
        $image->setTitle($paramFetcher->get('title'));

        $imageSaveSuccess = file_put_contents($image->getImagePath(), $imageFile);
        $view = View::create();

        if($imageSaveSuccess) {
            $this->getDoctrine()->getEntityManager()->persist($image);
            $this->getDoctrine()->getEntityManager()->flush();
        }

        return $imageSaveSuccess ? $view->setData($image)->setStatusCode(200) : $view->setStatusCode(400);
    }

    /**
     * Returns image by id.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Finds image from database.",
     *   statusCodes = {
     *     200 = "Returned when image successfully returned.",
     *     400 = "Returned when image was not found."
     *   }
     * )
     *
     * @param string $slug
     *
     * @return View
     */
    public function getAction($slug)
    {
        $image = $this->getDoctrine()->getRepository("GalerijaAPIBundle:Image")->find($slug);
        $view = View::create();

        return $image ? $view->setData($image)->setStatusCode(200) : $view->setStatusCode(404);
    }

    /**
     * Returns all images.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Finds image from database.",
     *   statusCodes = {
     *     200 = "Returned when images are successfully returned.",
     *   }
     * )
     *
     * @return View
     */
    public function getAllAction()
    {
        $images = $this->getDoctrine()->getEntityManager()->getRepository("GalerijaAPIBundle:Image")->findAll();
        $view = View::create();

        return $view->setData($images)->setStatusCode(200);
    }

    /**
     * Deletes image by id.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Finds image from database.",
     *   statusCodes = {
     *     200 = "Returned when image was successfully deleted..",
     *     400 = "Returned when image was not found."
     *   }
     * )
     *
     * @param string $slug
     *
     * @return View
     */
    public function deleteAction($slug)
    {
        $view = View::create();
        $image = $this->getDoctrine()->getRepository("GalerijaAPIBundle:Image")->find($slug);

        if(!$image) {
            return $view->setStatusCode(404);
        }

        $this->getDoctrine()->getManager()->detach($image);
        $this->getDoctrine()->getManager()->flush();

        unlink($image->getImagePath());

        return $view->setData("Image successfully deleted.")->setStatusCode(200);
    }

    public function patchAction()
    {

    }

}
