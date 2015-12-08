<?php

namespace Galerija\APIBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use Imagine\Exception\InvalidArgumentException;
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
     * @RequestParam(name="title", nullable=false, strict=true, description="Title.")
     * @RequestParam(name="data", nullable=false, strict=true, description="Image data.")
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
        $image->setStorageId($this->get('galerija_api.images.storage')->getStorageId());

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
        $image->setStorageId($this->get('galerija_api.images.storage')->getStorageId());

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
     *
     * )
     * @QueryParam(name="storageId", nullable=true, strict=false, description="Storage to get images from.")
     * @return View
     */
    public function getAllAction(ParamFetcher $paramFetcher)
    {
        $storage = $paramFetcher->get('storageId') !== null ? $paramFetcher->get('storageId') : 'local';
        $images = $this->getDoctrine()->getEntityManager()->getRepository("GalerijaAPIBundle:Image")->findBy(['storageId' => $storage]);
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
     * @RequestParam(name="image_ids", nullable=false, strict=false, description="Image of ids to work with.")
     * @RequestParam(name="mode", nullable=false, strict=true, description="Which patch method to use.")
     * @RequestParam(name="title", nullable=false, strict=false, description="Title to give new image.")
     * @RequestParam(name="column_num", nullable=true, strict=false, description="Which patch method to use.")
     * @RequestParam(name="watermark", nullable=true, strict=false, description="Which photo to use as watermark.")
     * @RequestParam(name="width", nullable=true, strict=false, description="New photo width on resize.")
     * @RequestParam(name="height", nullable=true, strict=false, description="New photo height on resize.")
     *
     * @return View
     */
    public function patchAction(ParamFetcher $paramFetcher)
    {
        $imService = $this->container->get('galerija_api.images.image_manipulation');
        $imageIds = json_decode($paramFetcher->get('image_ids'));
        switch($paramFetcher->get('mode')) {
            case 'collage':
                $image = $imService->makeCollage(
                    $imageIds,
                    $paramFetcher->get('title'),
                    $paramFetcher->get('column_num')
                );
                return  View::create()->setData($image)->setStatusCode(200);
            case 'watermark':
                $images = $imService->addWatermark(
                    $imageIds,
                    $paramFetcher->get('watermark')
                );
                return  View::create()->setData($images)->setStatusCode(200);
            case 'resize':
                try {
                    $images = $imService->resizeImages(
                        $imageIds,
                        $paramFetcher->get('width'),
                        $paramFetcher->get('height')
                    );
                } catch (InvalidArgumentException $ex) {
                    return  View::create()->setData('Watermark image is too big.')->setStatusCode(407);
                }

                return  View::create()->setData($images)->setStatusCode(200);
            default:
                return  View::create()->setData('Incorrect patch mode.')->setStatusCode(406);
        }
    }
}
