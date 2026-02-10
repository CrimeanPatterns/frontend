<?php

namespace AwardWallet\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Entity\ImageInterface;
use Psr\Http\Message\StreamInterface;

abstract class AbstractImageS3Storage extends AbstractS3Storage implements ImageStorageInterface
{
    public function getStreamByImage(ImageInterface $image): ?StreamInterface
    {
        return $this->get($image->getStorageKey());
    }

    public function deleteByImage(ImageInterface $image): void
    {
        $this->delete($image->getStorageKey());
    }
}
