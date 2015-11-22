<?php
/**
 * Created by PhpStorm.
 * User: Zylius
 * Date: 11/22/2015
 * Time: 21:22
 */

namespace Galerija\APIBundle\Images;

use Gaufrette\Filesystem;

class ImageStoreService
{
    /**
     * @var Filesystem
     */
    private $files;

    /**
     * ImageStoreService constructor.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
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
}