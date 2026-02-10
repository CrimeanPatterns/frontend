<?php

namespace AwardWallet\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\BufferStream;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractS3Storage implements StorageInterface
{
    private LoggerInterface $logger;
    private Encryptor $encryptor;
    private S3Client $s3Client;
    private string $bucket;

    public function __construct(
        Encryptor $encryptor,
        S3Client $s3Client,
        string $bucket,
        LoggerInterface $logger
    ) {
        $this->encryptor = $encryptor;
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
        $this->logger = $logger;
    }

    public function get(string $key): ?StreamInterface
    {
        $origStreamData = $this->getObject($key);

        if (null === $origStreamData) {
            return null;
        }

        $origStreamData = (string) $origStreamData;
        $newStreamData = $this->encryptor->decrypt($origStreamData);
        $stream = new BufferStream(\mb_strlen($newStreamData, '8bit') + 1);
        $stream->write($newStreamData);

        return $stream;
    }

    public function isExists(string $key): bool
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (AwsException $e) { // TODO: narrow to s3-specific exceptions
            $this->logger->warning('S3 exists exception: ' . $e->getMessage());
        }

        return false;
    }

    public function put(string $key, $content, ?\DateTime $expires = null): void
    {
        $this->putObject($key, $this->encryptor->encrypt($content), $expires);
    }

    public function delete(string $key): void
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
        } catch (AwsException $e) { // TODO: narrow to s3-specific exceptions
            $this->logger->warning('S3 delete exception: ' . $e->getMessage());
        }
    }

    private function getObject(string $key): ?StreamInterface
    {
        $object = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);

        return $object['Body'];
    }

    private function putObject(string $key, $content, ?\DateTime $expires = null): void
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $content,
            'Expires' => $expires ?? new \DateTime('+100 years'),
        ]);
    }
}
