<?php
/**
 * Created by PhpStorm.
 * User: Zylius
 * Date: 11/22/2015
 * Time: 21:22
 */

namespace Galerija\APIBundle\Images;

use Gaufrette\Filesystem;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\HttpFoundation\RequestStack;

class ImageStoreService
{
    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var integer
     */
    private $uId = null;

    /**
     * ImageStoreService constructor.
     *
     * @param FilesystemMap $files
     * @param RequestStack $request
     * @param \Dropbox_OAuth_Curl $dropbox
     */
    public function __construct(FilesystemMap $files, RequestStack $request, \Dropbox_OAuth_Curl $dropbox)
    {
        if($request->getCurrentRequest()->getSession()->get('dropbox') !== null) {
            $this->files = $files->get('pictures_dropbox');
            $dropbox->setToken($request->getCurrentRequest()->getSession()->get('dropbox')['access']);
            $this->uId = $request->getCurrentRequest()->getSession()->get('dropbox')['uid'];
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
     * Returns storage id.
     *
     * @return string
     */
    public function getStorageId() {
        return $this->uId ? $this->uId : 'local';
    }
}