<?php

namespace AwardWallet\Tests\Unit\MainBundle\Manager\CardImage;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use AwardWallet\MainBundle\Service\Storage\CardImageStorage;
use AwardWallet\Tests\Unit\BaseTest;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Psr7\BufferStream;
use Prophecy\Argument;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Manager\CardImage\CardImageManager
 */
class CardImageManagerTest extends BaseTest
{
    public function testGetImageStreamForOldUnencryptedImage()
    {
        $cardImage = new CardImage();
        $cardImage->setStorageKey('v1_abdlkfdlfkj');

        $encryptedStorage = $this->prophesize(CardImageStorage::class);
        $bufferStream = new BufferStream();
        $encryptedStorage
            ->getStreamByImage($cardImage)
            ->willReturn($bufferStream)
            ->shouldBeCalledOnce();

        /** @var CardImageManager $cardImageManager */
        $cardImageManager = $this->makeProphesizedMuted(CardImageManager::class, [
            '$storage' => $encryptedStorage->reveal(),
        ]);
        $actualStream = $cardImageManager->getImageStream($cardImage);

        $this->assertSame($actualStream, $bufferStream);
    }

    public function testSaveImageShouldSaveEncryptedContent()
    {
        $user = new Usr();
        $reflProp = new \ReflectionProperty($user, 'userid');
        $reflProp->setAccessible(true);
        $reflProp->setValue($user, $userId = 1);
        $reflProp->setAccessible(false);

        $imageContent = \file_get_contents(codecept_data_dir('cardImages/back.png'));
        $cardImageRep = $this->prophesize(EntityRepository::class);
        $storageKeyCaptured = null;
        $cardImageRep
            ->findOneBy(Argument::that(function (array $data) use ($userId, &$storageKeyCaptured) {
                $storageKeyCaptured = $data['storageKey'];

                return $data['userId'] === $userId;
            }))
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $encryptedStorage = $this->prophesize(CardImageStorage::class);
        $encryptedStorage
            ->isExists(Argument::that(function (string $storageKey) use (&$storageKeyCaptured) {
                $storageKeyCaptured = $storageKey;

                return true;
            }))
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $encryptedStorage
            ->put(
                Argument::that(function (string $storageKey) use (&$storageKeyCaptured) {
                    return $storageKeyCaptured === $storageKey;
                }),
                $imageContent
            )
            ->shouldBeCalledOnce();

        $entityManager = $this->prophesize(EntityManager::class);
        $entityManager
            ->persist(Argument::type(CardImage::class))
            ->shouldBeCalledOnce();

        $entityManager
            ->flush(Argument::that(function (CardImage $cardImage) use (&$storageKeyCaptured) {
                return $cardImage->getStorageKey() === $storageKeyCaptured;
            }))
            ->shouldBeCalledOnce();

        /** @var CardImageManager $cardImageManager */
        $cardImageManager = $this->makeProphesizedMuted(CardImageManager::class, [
            '$storage' => $encryptedStorage->reveal(),
            '$entityManager' => $entityManager->reveal(),
            '$cardImageRep' => $cardImageRep->reveal(),
        ]);

        $cardImageManager->saveImage($user, 'back.png', $imageContent);
    }
}
