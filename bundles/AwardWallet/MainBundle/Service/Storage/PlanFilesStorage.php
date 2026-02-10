<?php

namespace AwardWallet\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Entity\Files\AbstractFile;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use Aws\S3\S3Client;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class PlanFilesStorage extends AbstractS3Storage implements FileStorageInterface
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
                '%s-%s%s',
                $s3BucketPrefix,
                'plan',
                'attachmentbucket'
            ),
            $logger
        );
    }

    public function getStreamByFile(AbstractFile $planFile): StreamInterface
    {
        return $this->get($planFile->getStorageKey());
    }

    public function deleteByFile(AbstractFile $planFile): void
    {
        $this->delete($planFile->getStorageKey());
    }
}
