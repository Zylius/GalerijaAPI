<?php

namespace Galerija\APIBundle\Images;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Galerija\APIBundle\Entity\Image;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine;

class ImageManipulationService
{
    /**
     * @var $em EntityManager
     */
    private $em;

    /**
     * @var $repository EntityRepository
     */
    private $repository;

    /**
     * @var $imagine ImagineInterface
     */
    private $imagine;

    /**
     * @var $baseDir string
     */
    private $baseDir;

    /**
     * ImageManipulationService constructor.
     *
     * @param EntityManager $em
     * @param string $repository
     * @param ImagineInterface $imagine
     * @param string $baseDir
     */
    public function __construct(EntityManager $em, $repository, ImagineInterface $imagine, $baseDir)
    {
        $this->em = $em;
        $this->repository = $this->em->getRepository($repository);
        $this->imagine = $imagine;
        $this->baseDir = $baseDir;
    }

    /**
     * Returns a 2x* collage of given images.
     *
     * @param int[]  $imageIds
     * @param int    $numColumns
     * @param string $title
     *
     * @return Image
     */
    public function makeCollage($imageIds, $title, $numColumns = 2)
    {
        if(!$numColumns) {
            $numColumns = 2;
        }
        /** @var Image[] $imageRecords */
        $imageRecords = $this->repository->findBy(['id' => $imageIds]);
        $photos = [];
        foreach ($imageRecords as $record) {
            $photos[] = $this->imagine->load($record->getImagePath());
        }
        $photos = $this->sortPhotosByHeight($photos);

        $imagesPerColumn = sizeof($photos) / $numColumns;
        $columns = array_chunk($photos, $imagesPerColumn);
        $normalizedColumns = $this->findImagePositions($columns);
        $boxSize = $this->findBoxSize($normalizedColumns);
        $collage = $this->imagine->create($boxSize);

        foreach ($columns as $column) {
            foreach ($column as  $image) {
                $collage->paste($image['image'], $image['top']);
            }
        }

        $image = new Image();
        $image->setImagePath('images/' . uniqid() . '.jpg');
        $image->setTitle($title);
        $collage->save($image->getImagePath());

        $this->em->persist($image);
        $this->em->flush();

        return $image;
    }

    /**
     * Finds positions for images and returns an array with them.
     *
     * @param ImageInterface[][] $columns
     *
     * @return array
     */
    private function findImagePositions($columns) {
        $normalizedColumns = [];
        foreach ($columns as $columnKey => $column) {
            $x = 0;
            foreach ($column as $imageKey => $image) {
                $y = $columnKey > 0 ? $this->getColumnWidthAt($normalizedColumns[$columnKey - 1], $x) : 0;
                $normalizedColumns[$columnKey][$imageKey] = [
                    'top' => new Point($x, $y),
                    'bottom' => new Point($x + $image->getSize()->getHeight(), $y + $image->getSize()->getWidth()),
                    'image' => $image
                ];
            }
        }

        return $normalizedColumns;
    }

    /**
     * Returns box size.
     *
     * @param array $normalizedColumns
     *
     * @return Box
     */
    private function findBoxSize($normalizedColumns)
    {
        $box = new Box(0,0);
        foreach ($normalizedColumns as $columnKey => $column) {
            foreach ($column as $imageKey => $image) {
                /** @var Point $point */
                $point = $image['bottom'];
                if ($point->getY() >= $box->getHeight()) {
                    $box = new Box($box->getWidth(), $point->getY());
                }

                if ($point->getX() >= $box->getWidth()) {
                    $box = new Box($point->getX(), $box->getHeight());
                }
            }
        }

        return $box;
    }

    /**
     * Sorts photos by height.
     *
     * @param ImageInterface[] $photos
     *
     * @return ImageInterface[]
     */
    private function sortPhotosByHeight($photos)
    {
        usort(
            $photos,
            function ($a, $b) {
                /**
                 * @var ImageInterface $a
                 * @var ImageInterface $b
                 */
                return $a->getSize()->getHeight() > $b->getSize()->getHeight();
            }
        );

        return $photos;
    }
    /**
     * Returns supposed y position at specified x.
     *
     * @param array[] $column Column which Y to search for.
     * @param int              $xPos   At which position to get Y from.
     *
     * @return int
     */
    private function getColumnWidthAt($column, $xPos) {
        foreach($column as $item) {
            /** @var Point $bottom */
            $bottom = $item['bottom'];
            /** @var Point $top */
            $top = $item['bottom'];
            if ($top->getX() <= $xPos && $bottom->getX() >= $xPos) {
                return $bottom->getY();
            }
        }

        return 0;
    }
}