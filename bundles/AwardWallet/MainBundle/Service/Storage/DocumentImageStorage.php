<?php

namespace AwardWallet\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;

class DocumentImageStorage extends AbstractImageS3Storage
{
    public function __construct(Encryptor $encryptor, S3Client $s3Client, string $s3BucketPrefix, LoggerInterface $logger)
    {
        parent::__construct($encryptor, $s3Client, sprintf('%s-documentimagebucket', $s3BucketPrefix), $logger);
    }
}
