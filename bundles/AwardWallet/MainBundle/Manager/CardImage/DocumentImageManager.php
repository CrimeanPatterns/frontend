<?php

namespace AwardWallet\MainBundle\Manager\CardImage;

use AwardWallet\MainBundle\Entity\DocumentImage;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Storage\DocumentImageStorage;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Http\Message\StreamInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DocumentImageManager
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const MAX_WIDTH = 5000;
    public const MAX_HEIGHT = 5000;
    public const STORAGE_KEY_PREFIX = 'v1';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var EntityRepository
     */
    private $documentImageRep;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var Process
     */
    private $asyncProcess;

    private DocumentImageStorage $storage;

    public function __construct(
        EntityRepository $documentImageRep,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        Process $asyncProcess,
        DocumentImageStorage $storage
    ) {
        $this->entityManager = $entityManager;
        $this->documentImageRep = $documentImageRep;
        $this->authorizationChecker = $authorizationChecker;
        $this->asyncProcess = $asyncProcess;
        $this->storage = $storage;
    }

    /**
     * @return StreamInterface
     */
    public function getImageStream(DocumentImage $documentImage)
    {
        return $this->storage->getStreamByImage($documentImage);
    }

    /**
     * @return string|null
     */
    public function getImageContent(DocumentImage $documentImage)
    {
        $resource = $this->getImageStream($documentImage);

        if (null === $resource) {
            return null;
        }

        return (string) $resource;
    }

    /**
     * @param string $filename
     * @param string $content
     * @return DocumentImage|null
     * @throws \Exception
     */
    public function saveImage(Usr $user, $filename, $content, $imageId): DocumentImage
    {
        $imageValidator = new ImageValidator(self::MAX_BYTES, self::MAX_WIDTH, self::MAX_HEIGHT);
        $imageValidator->validateContent($filename, $content);

        $storageKey = $this->generateStorageKeyByMetadata(
            $user->getId(),
            hash('sha256', $content),
            $imageValidator->getWidth(),
            $imageValidator->getHeight(),
            $imageValidator->getMime()
        );

        /** @var DocumentImage $existingDocumentImage */
        $existingDocumentImage = $this->documentImageRep->findOneBy([
            'userId' => $user->getId(),
            'storageKey' => $storageKey,
        ]);

        if ($existingDocumentImage) {
            return $existingDocumentImage;
        }

        if (!$this->storage->isExists($storageKey)) {
            $this->storage->put($storageKey, $content);
        }

        $documentImage = null;

        if ($imageId) {
            $documentImage = $this->documentImageRep->find($imageId);
        }

        if (!$documentImage) {
            $documentImage = new DocumentImage();
        }

        $documentImage->setUser($user)
            ->setFileName($filename)
            ->setFileSize($imageValidator->getSize())
            ->setWidth($imageValidator->getWidth())
            ->setHeight($imageValidator->getHeight())
            ->setFormat($imageValidator->getMime())
            ->setStorageKey($storageKey)
            ->setUUID(Uuid::uuid4())
            ->setUploadDate(new \DateTime());
        $this->entityManager->persist($documentImage);
        $this->entityManager->flush($documentImage);

        return $documentImage;
    }

    public function saveUploadedImage(Usr $user, UploadedFile $file, $imageId = null): DocumentImage
    {
        $imageValidator = new ImageValidator(self::MAX_BYTES, self::MAX_WIDTH, self::MAX_HEIGHT);
        $imageValidator->validateUpload($file);

        return $this->saveImage($user, $file->getClientOriginalName(), file_get_contents($file->getPathname()), $imageId);
    }

    public function deleteImage(DocumentImage $documentImage)
    {
        $this->storage->deleteByImage($documentImage);

        $coupon = $documentImage->getProviderCoupon();
        $coupon->removeDocumentImage($documentImage);

        $this->entityManager->remove($documentImage);
        $this->entityManager->flush();
    }

    /**
     * @param string $storageKey
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function deleteImageById($documentImageId, $storageKey)
    {
        $this->storage->delete($storageKey);
        $this->entityManager->getConnection()->delete(
            'DocumentImage',
            ['DocumentImageID' => $documentImageId],
            ['DocumentImageID' => \PDO::PARAM_INT]
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
        return sprintf('%s_%s_%s',
            self::STORAGE_KEY_PREFIX,
            $userId,
            $contentHash
        );
    }
}
