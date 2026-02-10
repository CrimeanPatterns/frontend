<?php

namespace Codeception\Module;

use AwardWallet\MainBundle\FrameworkExtension\TestS3Client;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Codeception\Module;
use Codeception\TestCase;

use function PHPUnit\Framework\assertNotNull;

class AwsS3 extends Module
{
    /**
     * @var S3Client
     */
    protected $client;
    /**
     * @var Encryptor
     */
    protected $encryptor;

    public function _before(TestCase $test)
    {
        parent::_before($test);

        if ($this->hasModule('Symfony')) {
            /** @var Symfony $symfony2 */
            $symfony2 = $this->getModule('Symfony');
            $container = $symfony2->_getContainer();

            if ($container->has(S3Client::class)) {
                $this->client = $container->get(S3Client::class);
            }

            if ($container->has(Encryptor::class)) {
                $this->encryptor = $container->get(Encryptor::class);
            }
        }
    }

    public function _after(TestCase $test)
    {
        if ($this->client instanceof TestS3Client) {
            $this->client->clear();
        }
    }

    public function seeS3Object(string $bucket, string $key, ?string $expectedContent = null, bool $isEncrypted = false)
    {
        if (!$this->isObjectExists($bucket, $key)) {
            $this->fail("Key \"{$key}\" not found in bucket \"{$bucket}\"");
        }

        if (isset($expectedContent)) {
            $rawData = $this->grabS3Object($bucket, $key);

            if ($isEncrypted) {
                $rawData = $this->encryptor->decrypt($rawData);
            }

            if ($expectedContent !== $rawData) {
                $this->fail('content mismatch');
            }
        }
    }

    public function dontSeeS3Object($bucket, $key)
    {
        if ($this->isObjectExists($bucket, $key)) {
            $this->fail("Key \"{$key}\" found in bucket \"{$bucket}\"");
        }
    }

    public function grabS3Object($bucket, $key)
    {
        assertNotNull($this->client);
        $this->seeS3Object($bucket, $key);

        return (string) $this->client->getObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ])['Body'];
    }

    public function haveS3Object(string $bucket, string $key, string $content, bool $encrypt = false)
    {
        assertNotNull($this->client);
        $this->client->putObject([
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => $encrypt ? $this->encryptor->encrypt($content) : $content,
        ]);
    }

    /**
     * @param string $bucket
     * @param string $key
     * @return bool
     */
    protected function isObjectExists($bucket, $key)
    {
        assertNotNull($this->client);

        try {
            $this->client->headObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }
}
