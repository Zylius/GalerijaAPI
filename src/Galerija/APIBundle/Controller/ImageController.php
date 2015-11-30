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

        $image->setTitle($paramFetcher->get('title'));

        try {
            $image->setImagePath($this->get('galerija_api.images.storage')->saveImage($imageFile));
        } catch (\RuntimeException $ex) {
            return $view->setStatusCode(400);
        }

        $this->get('galerija_api.images.storage')->deleteImage($lastImage->getImagePath());
        $em->persist($image);
        $em->flush();

        return $view->setData($image)->setStatusCode(200);
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

        $image->setTitle($paramFetcher->get('title'));


        $view = View::create();

        try {
            $image->setImagePath($this->get('galerija_api.images.storage')->saveImage($imageFile));
        } catch (\RuntimeException $ex) {
            return $view->setStatusCode(400);
        }

        $this->getDoctrine()->getEntityManager()->persist($image);
        $this->getDoctrine()->getEntityManager()->flush();

        return $view->setData($image)->setStatusCode(200);
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
     *     200 = "Returned when image was successfully deleted.",
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

        $this->getDoctrine()->getManager()->remove($image);
        $this->getDoctrine()->getManager()->flush();

        $this->get('galerija_api.images.storage')->deleteImage($image->getImagePath());

        return $view->setData(['status' => "Image successfully deleted."])->setStatusCode(200);
    }

    /**
     * Deletes image by id.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Patches specified images.",
     *   statusCodes = {
     *     200 = "Returned when image patched successfully.",
     *     400 = "Returned when one or more of the images were not found."
     *   }
     * )
     *
     * @QueryParam(name="image_ids", nullable=false, strict=true, description="Image of ids to work with.")
     * @QueryParam(name="mode", nullable=false, strict=true, description="Which patch method to use.")
     * @QueryParam(name="title", nullable=false, strict=false, description="Title to give new image.")
     * @QueryParam(name="column_num", nullable=true, strict=false, description="Which patch method to use.")
     * @QueryParam(name="watermark", nullable=true, strict=false, description="Which photo to use as watermark.")
     * @QueryParam(name="width", nullable=true, strict=false, description="New photo width on resize.")
     * @QueryParam(name="height", nullable=true, strict=false, description="New photo height on resize.")
     *
     * @return View
     */
    public function patchAction(ParamFetcher $paramFetcher)
    {
        $imService = $this->container->get('galerija_api.images.image_manipulation');
        switch($paramFetcher->get('mode')) {
            case 'collage':
                $image = $imService->makeCollage(
                    json_decode($paramFetcher->get('image_ids')),
                    $paramFetcher->get('title'),
                    $paramFetcher->get('column_num')
                );
                return  View::create()->setData($image)->setStatusCode(200);
            case 'watermark':
                $images = $imService->addWatermark(
                    json_decode($paramFetcher->get('image_ids')),
                    $paramFetcher->get('watermark')
                );
                return  View::create()->setData($images)->setStatusCode(200);
            case 'resize':
                $images = $imService->resizeImages(
                    json_decode($paramFetcher->get('image_ids')),
                    $paramFetcher->get('width'),
                    $paramFetcher->get('height')
                );
                return  View::create()->setData($images)->setStatusCode(200);
            default:
                return  View::create()->setData('Incorrect patch mode.')->setStatusCode(406);
        }
    }
}
