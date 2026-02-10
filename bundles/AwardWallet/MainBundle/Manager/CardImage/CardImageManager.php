<?php

namespace AwardWallet\MainBundle\Manager\CardImage;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Storage\CardImageStorage;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CardImageManager
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const MAX_WIDTH = 5000;
    public const MAX_HEIGHT = 5000;
    private const STORAGE_KEY_PREFIX = 'v1';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var EntityRepository
     */
    private $cardImageRep;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var Process
     */
    private $asyncProcess;

    private CardImageStorage $storage;

    public function __construct(
        EntityRepository $cardImageRep,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        Process $asyncProcess,
        CardImageStorage $storage
    ) {
        $this->entityManager = $entityManager;
        $this->cardImageRep = $cardImageRep;
        $this->authorizationChecker = $authorizationChecker;
        $this->asyncProcess = $asyncProcess;
        $this->storage = $storage;
    }

    /**
     * @return StreamInterface
     */
    public function getImageStream(CardImage $cardImage)
    {
        return $this->storage->getStreamByImage($cardImage);
    }

    /**
     * @return string|null
     */
    public function getImageContent(CardImage $cardImage)
    {
        $resource = $this->getImageStream($cardImage);

        if (null === $resource) {
            return null;
        }

        return (string) $resource;
    }

    /**
     * @param string $filename
     * @param string $content
     * @return CardImage|null
     */
    public function saveImage(Usr $user, $filename, $content): CardImage
    {
        $imageValidator = new ImageValidator(self::MAX_BYTES, self::MAX_WIDTH, self::MAX_HEIGHT);
        $imageValidator->validateContent($filename, $content);

        $storageKey = $this->generateStorageKeyByMetadata(
            $user->getUserid(),
            hash('sha256', $content),
            $imageValidator->getWidth(),
            $imageValidator->getHeight(),
            $imageValidator->getMime()
        );

        /** @var CardImage $existingCardImage */
        $existingCardImage = $this->cardImageRep->findOneBy([
            'userId' => $user->getUserid(),
            'storageKey' => $storageKey,
        ]);

        if ($existingCardImage) {
            return $existingCardImage;
        }

        if (!$this->storage->isExists($storageKey)) {
            $this->storage->put($storageKey, $content);
        }

        $cardImage = (new CardImage())
            ->setUser($user)
            ->setFileName($filename)
            ->setFileSize($imageValidator->getSize())
            ->setWidth($imageValidator->getWidth())
            ->setHeight($imageValidator->getHeight())
            ->setFormat($imageValidator->getMime())
            ->setStorageKey($storageKey)
            ->setUploadDate(new \DateTime());
        $this->entityManager->persist($cardImage);
        $this->entityManager->flush($cardImage);

        return $cardImage;
    }

    public function saveUploadedImage(Usr $user, UploadedFile $file): CardImage
    {
        $imageValidator = new ImageValidator(self::MAX_BYTES, self::MAX_WIDTH, self::MAX_HEIGHT);
        $imageValidator->validateUpload($file);

        return $this->saveImage($user, $file->getClientOriginalName(), file_get_contents($file->getPathname()));
    }

    public function deleteImage(CardImage $cardImage)
    {
        $this->storage->deleteByImage($cardImage);

        if ($container = ($cardImage->getAccount() ?: ($cardImage->getProviderCoupon() ?: $cardImage->getSubAccount()))) {
            $container->removeCardImage($cardImage);
        }

        $this->entityManager->remove($cardImage);
        $this->entityManager->flush();
    }

    /**
     * @param int $cardImageId
     * @param string $storageKey
     */
    public function deleteImageById($cardImageId, $storageKey)
    {
        $this->storage->delete($storageKey);
        $this->entityManager->getConnection()->delete(
            'CardImage',
            ['CardImageID' => $cardImageId],
            ['CardImageID' => \PDO::PARAM_INT]
        );
    }

    /**
     * @param int $userId
     * @param string $contentHash
     * @param int $width
     * @param int $height
     * @param string $mime
     * @return string
     */
    protected function generateStorageKeyByMetadata($userId, $contentHash, $width, $height, $mime)
    {
        return $this->generateStorageByParts(
            $userId,
            hash('sha256', "{$contentHash}_{$width}_{$height}_{$mime}_" . StringHandler::getPseudoRandomString(64))
        );
    }

    /**
     * @param int $userId
     * @param string $contentHash
     * @return string
     */
    protected function generateStorageByParts($userId, $contentHash)
    {
        return \sprintf(
            '%s_%s_%s',
            self::STORAGE_KEY_PREFIX,
            $userId,
            $contentHash
        );
    }
}
