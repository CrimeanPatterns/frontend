<?php

namespace AwardWallet\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Entity\Files\AbstractFile;
use Psr\Http\Message\StreamInterface;

interface FileStorageInterface
{
    public function getStreamByFile(AbstractFile $file): StreamInterface;

    public function deleteByFile(AbstractFile $file): void;
}
