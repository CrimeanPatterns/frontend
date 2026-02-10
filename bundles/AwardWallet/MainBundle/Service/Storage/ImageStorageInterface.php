<?php

namespace AwardWallet\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Entity\ImageInterface;
use Psr\Http\Message\StreamInterface;

interface ImageStorageInterface
{
    public function getStreamByImage(ImageInterface $image): ?StreamInterface;

    public function deleteByImage(ImageInterface $image): void;
}
