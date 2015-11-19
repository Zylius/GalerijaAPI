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
     * Adds specifed watermark to given images.
     *
     * @param int[] $imageIds
     * @param int   $watermarkImageId
     *
     * @return Image[]
     */
    public function addWatermark($imageIds, $watermarkImageId)
    {
        /** @var Image[] $imageRecords */
        $imageRecords = $this->repository->findBy(['id' => $imageIds]);
        $watermarkRecord = $this->repository->find($watermarkImageId);

        $photos = [];
        foreach ($imageRecords as $key => $record) {
            $photos[$key] = $this->imagine->open($record->getImagePath());
        }
        $watermark = $this->imagine->open($watermarkRecord->getImagePath());

        foreach ($photos as $key => $photo) {
            $image = $this->applyWatermark($photo, $watermark);
            $image->save($imageRecords[$key]->getImagePath());
            $this->em->persist($imageRecords[$key]);
        }
        $this->em->flush();

        return $imageRecords;
    }

    /**
     * Applies watermark to image.
     *
     * @param ImageInterface   $image
     * @param ImageInterface $watermark
     *
     * @return ImageInterface
     */
    private function applyWatermark(ImageInterface $image, ImageInterface $watermark)
    {
        $size = $image->getSize();
        $wSize = $watermark->getSize();

        $bottomRight = new Point($size->getWidth() - $wSize->getWidth(), $size->getHeight() - $wSize->getHeight());

        $image->paste($watermark, $bottomRight);

        return $image;
    }

    /**
     * Returns a <numColumns>x* collage of given images.
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
            $photos[] = $this->imagine->load(file_get_contents($record->getImagePath()));
        }
        $photos = $this->sortPhotosByHeight($photos);

        $imagesPerColumn = ceil(sizeof($photos) / $numColumns);
        $columns = array_chunk($photos, $imagesPerColumn);
        $normalizedColumns = $this->findImagePositions($columns);
        $boxSize = $this->findBoxSize($normalizedColumns);
        $collage = $this->imagine->create($boxSize);

        foreach ($normalizedColumns as $column) {
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
            $y = 0;
            foreach ($column as $imageKey => $image) {
                $x = $columnKey > 0 ? $this->getColumnWidthAt($normalizedColumns[$columnKey - 1], $y) : 0;
                $normalizedColumns[$columnKey][$imageKey] = [
                    'top' => new Point($x, $y),
                    'bottom' => new Point($x + $image->getSize()->getWidth(), $y + $image->getSize()->getHeight()),
                    'image' => $image
                ];
                $y += $image->getSize()->getHeight();
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
        $box = new Box(1,1);
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
                return $a->getSize()->getHeight() < $b->getSize()->getHeight();
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
            $top = $item['top'];
            if ($top->getX() <= $xPos && $bottom->getX() >= $xPos) {
                return $bottom->getX();
            }
        }

        return 0;
    }
}