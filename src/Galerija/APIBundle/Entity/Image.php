<?php

namespace Galerija\APIBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Image
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Image
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="imagePath", type="string", length=255)
     */
    private $imagePath;

    /**
     * @var string
     *
     * @ORM\Column(name="storageId", type="string", length=255)
     */
    private $storageId;

    /**
     * @var string
     */
    private $imageData;

    /**
     * @return string
     */
    public function getImageData()
    {
        return $this->imageData;
    }

    /**
     * @param string $imageData
     */
    public function setImageData($imageData)
    {
        $this->imageData = $imageData;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Image
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set imagePath
     *
     * @param string $imagePath
     *
     * @return Image
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * Get imagePath
     *
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Set storageId
     *
     * @param string $storageId
     *
     * @return Image
     */
    public function setStorageId($storageId)
    {
        $this->storageId = $storageId;

        return $this;
    }

    /**
     * Get storageId
     *
     * @return string
     */
    public function getStorageId()
    {
        return $this->storageId;
    }
}

