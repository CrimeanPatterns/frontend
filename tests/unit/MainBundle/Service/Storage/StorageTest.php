<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Storage;

use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use AwardWallet\MainBundle\Service\Storage\CardImageStorage;
use AwardWallet\MainBundle\Service\Storage\DocumentImageStorage;
use AwardWallet\MainBundle\Service\Storage\PlanFilesStorage;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Aws\S3\S3Client;
use Codeception\Stub\Expected;
use GuzzleHttp\Psr7\BufferStream;
use Psr\Log\Test\TestLogger;

/**
 * @group frontend-unit
 */
class StorageTest extends BaseContainerTest
{
    /**
     * @dataProvider storageProvider
     */
    public function test(string $class)
    {
        $content = 'content';
        $encryptedContent = 'encrypted_content';
        $bufferStream = new BufferStream();
        $bufferStream->write($encryptedContent);

        $storage = new $class(
            $this->make(Encryptor::class, [
                'encrypt' => Expected::once($encryptedContent),
                'decrypt' => Expected::once($content),
            ]),
            $this->make(S3Client::class, [
                '__call' => ['Body' => $bufferStream],
            ]),
            'test',
            new TestLogger()
        );

        $storage->put('key', $content);
        $this->assertEquals($content, $storage->get('key'));
    }

    public function storageProvider()
    {
        return [
            [CardImageStorage::class],
            [DocumentImageStorage::class],
            [PlanFilesStorage::class],
        ];
    }
}
