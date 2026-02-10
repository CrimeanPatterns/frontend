<?php

namespace AwardWallet\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Entity\Files\AbstractFile;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use Aws\S3\S3Client;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class ItineraryFileStorage extends AbstractS3Storage implements FileStorageInterface
{
    public function __construct(
        Encryptor $encryptor,
        S3Client $s3Client,
        string $s3BucketPrefix,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $encryptor,
            $s3Client,
            sprintf(
                'aw-%s-%s-%s',
                $s3BucketPrefix,
                'segment',
                'attachments'
            ),
            $logger
        );
    }

    public function getStreamByFile(AbstractFile $file): StreamInterface
    {
        return $this->get($file->getStorageKey());
    }

    public function deleteByFile(AbstractFile $file): void
    {
        $this->delete($file->getStorageKey());
    }
}
