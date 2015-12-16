<?php
/**
 * Created by PhpStorm.
 * User: Zylius
 * Date: 11/22/2015
 * Time: 21:22
 */

namespace Galerija\APIBundle\Images;

use Galerija\APIBundle\Entity\Image;
use Gaufrette\Filesystem;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session;

class ImageStoreService
{
    /**
     * @const COOKIE_NAME
     */
    const DROPBOX_STORAGE_NAME = 'dropbox_auth';

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var integer
     */
    private $uId = null;

    /**
     * @var Request
     */
    private $request;

    /**
     * ImageStoreService constructor.
     *
     * @param FilesystemMap $files
     * @param RequestStack $requestStack
     * @param \Dropbox_OAuth_Curl $dropbox
     */
    public function __construct(FilesystemMap $files, RequestStack $requestStack, \Dropbox_OAuth_Curl $dropbox)
    {
        $this->request = $requestStack->getCurrentRequest();
        if (
            $this->request !== null &&
            ($data = $this->request->getSession()->get($this->request->get(self::DROPBOX_STORAGE_NAME))) !== null
        ) {
            $this->files = $files->get('pictures_dropbox');
            $dropbox->setToken($data['access']);
            $this->uId = $data['uid'];
        } else {
            $this->files = $files->get('pictures');
        }
    }

    /**
     * Saves an image.
     *
     * @param string $path
     * @param string $image
     *
     * @return string
     */
    public function saveImage($image, $path = null)
    {
        if ($path == null) {
            $path = 'images/' . uniqid() . '.png';
        }
        $this->files->write($path, $image, true);

        return $path;
    }

    /**
     * Deletes an image.
     *
     * @param string $imagePath
     *
     * @return boolean
     */
    public function deleteImage($imagePath) {
        return $this->files->delete($imagePath);
    }

    /**
     * Returns an image encoded.
     *
     * @param string $imagePath
     *
     * @return string
     */
    public function getImageBase64($imagePath) {
        $this->files->clearFileRegister();
        return base64_encode($this->files->get($imagePath)->getContent());
    }

    /**
     * Returns raw image.
     *
     * @param string $imagePath
     *
     * @return string
     */
    public function getImage($imagePath) {
        return $this->files->get($imagePath)->getContent();
    }

    /**
     * Saves new storage to session.
     *
     * @param array $data
     *
     * @return string
     */
    public function saveStorage($data) {
        $cookieCode = md5($data['uid']);
        $this->request->getSession()->set(
            $cookieCode,
            $data
        );
        return $cookieCode;
    }

    /**
     * Sets base64 image data
     *
     * @param  Image[]|Image $images
     */
    public function setImageData($images) {
        $images = $images instanceof Image ? [$images] : $images;
        foreach($images as $image) {
            $image->setImageData($this->getImageBase64($image->getImagePath()));
        }
    }

    /**
     * Returns storage id.
     *
     * @return string
     */
    public function getStorageId() {
        return $this->uId ? $this->uId : 'local';
    }

    /**
     * Returns storage type based on cookie.
     *
     * @return string
     */
    public function getStorageTypeFromCookie() {
        return $this->request->cookies->get(self::DROPBOX_STORAGE_NAME) ?
            $this->request->cookies->get(self::DROPBOX_STORAGE_NAME) : 'local';
    }
}