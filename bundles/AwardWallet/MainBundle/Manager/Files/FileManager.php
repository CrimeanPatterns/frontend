<?php

namespace AwardWallet\MainBundle\Manager\Files;

use AwardWallet\MainBundle\Entity\Files\AbstractFile;
use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\StreamCopyResponse;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Storage\FileStorageInterface;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FileManager
{
    public const STORAGE_KEY_PREFIX = 'v1';
    public const MAX_FILE_SIZE = 16777216;

    // this list is in file Notes.tsx
    public const ALLOW_MIME_TYPES = [
        'image/jpeg',
        'image/webp',
        'image/png',
        'text/plain',
        'text/csv',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.text',
        'application/rtf',
        'application/pdf',
    ];

    protected EntityManagerInterface $entityManager;
    protected AuthorizationCheckerInterface $authorizationChecker;
    private FileStorageInterface $fileStorage;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        FileStorageInterface $fileStorage,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->fileStorage = $fileStorage;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param AbstractFile[] $files
     */
    public function getListFiles(Collection $files): array
    {
        $result = [];

        foreach ($files as $file) {
            $result[$file->getId()] = [
                'id' => $file->getId(),
                'fileName' => $file->getFileName(),
                'fileSize' => $file->getFileSize(),
                'format' => $file->getFormat(),
                'description' => $file->getDescription(),
                'time' => $file->getUploadDate()->getTimestamp(),
                'uploadDate' => $file->getUploadDate()->format('c'),
            ];
        }

        return $result;
    }

    public function getFlatFiles(Collection $files): array
    {
        $list = $this->getListFiles($files);
        $result = [];

        foreach ($list as $file) {
            $tmp = $file;
            $tmp['name'] = $tmp['fileName'];
            $tmp['size'] = $tmp['fileSize'];
            $tmp['date'] = $tmp['uploadDate'];

            unset($tmp['fileName'], $tmp['fileSize'], $tmp['uploadDate']);
            $result[] = $tmp;
        }

        return $result;
    }

    public function saveUploadedFile(UploadedFile $file, Usr $user, string $entityClass): AbstractFile
    {
        $fileContent = file_get_contents($file->getPathname());
        $storageKey = $this->generateStorageKey($user->getId(), $fileContent);

        /** @var AbstractFile $fileExists */
        $fileExists = $this->entityManager->getRepository($entityClass)
            ->findOneBy([
                'storageKey' => $storageKey,
            ]);

        if ($fileExists) {
            return $fileExists;
        }

        if (!$this->fileStorage->isExists($storageKey)) {
            $this->fileStorage->put($storageKey, $fileContent);
        }

        $newFile = (new $entityClass())
            ->setFileName($file->getClientOriginalName())
            ->setFileSize($file->getSize())
            ->setFormat($file->getClientMimeType())
            ->setStorageKey($storageKey)
            ->setUploadDate(new \DateTime());

        return $newFile;
    }

    public function fetchResponse(AbstractFile $file, bool $isResponseStreaming): ?Response
    {
        $fileStream = $this->getFileStream($file);

        if (!isset($fileStream)) {
            return null;
        }

        $expireDate = (clone $file->getUploadDate())->modify('+10 years');

        if (!$isResponseStreaming) {
            $response = (new StreamCopyResponse(
                $fileStream,
                $file->getFileSize(),
                Response::HTTP_OK,
                ['Content-Type' => $file->getFormat()]
            ));
        } else {
            $response = new Response(
                $content = (string) $fileStream,
                Response::HTTP_OK,
                ['Content-Length' => strlen($content)]
            );
        }

        $response
            ->setExpires($expireDate)
            ->setLastModified($file->getUploadDate())
            ->setCache(['private' => true, 'max_age' => $expireDate->getTimestamp() - time()]);

        $fileName = (new Slugify(['lowercase' => false]))->slugify($file->getFileName(), ' ');
        $response->headers
            ->set(
                'Content-Disposition',
                $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName)
            );
        $response->headers->set('Pragma', '');

        return $response;
    }

    public function removeFile(AbstractFile $file, bool $isFlush = false): void
    {
        $this->fileStorage->deleteByFile($file);
        $this->entityManager->remove($file);

        if ($isFlush) {
            $this->entityManager->flush();
        }
    }

    public function getFileStream(AbstractFile $file): StreamInterface
    {
        return $this->fileStorage->getStreamByFile($file);
    }

    public function getFileContent(AbstractFile $file): ?string
    {
        $resource = $this->getFileStream($file);

        if (null === $resource) {
            return null;
        }

        return (string) $resource;
    }

    public function baseValidate(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \LengthException($this->translator->trans('card-pictures.error.big-file', ['$0' => round(self::MAX_FILE_SIZE / 1024 / 1024)]));
        }

        if (($file->getClientMimeType() !== $file->getMimeType() && !in_array($file->getMimeType(), ['text/plain']))
            || false === in_array($file->getMimeType(), self::ALLOW_MIME_TYPES, true)) {
            throw new \InvalidArgumentException($this->translator->trans('card-pictures.error.file-type', ['$0' => 'jpeg, webp, png, txt, csv, doc, xls, pdf, rtf, ppt']));
        }
    }

    public function removeAllFilesByUser(int $userId): array
    {
        return $this->removeAllFiles('itinerary.user = ' . $userId);
    }

    public function removeAllDetachedFiles(): array
    {
        return $this->removeAllFiles('itinerary.id IS NULL');
    }

    private function removeAllFiles(string $condition): array
    {
        $list = [];

        foreach (Itinerary::ITINERARY_KIND_TABLE as $kind => $kindId) {
            switch ($kind) {
                case Itinerary::KIND_TRIP:
                    $itineraryJoin = Trip::class;

                    break;

                case Itinerary::KIND_RESERVATION:
                    $itineraryJoin = Reservation::class;

                    break;

                case Itinerary::KIND_RENTAL:
                    $itineraryJoin = Rental::class;

                    break;

                case Itinerary::KIND_RESTAURANT:
                    $itineraryJoin = Restaurant::class;

                    break;

                case Itinerary::KIND_PARKING:
                    $itineraryJoin = Parking::class;

                    break;

                default:
                    throw new \RuntimeException('Unknown kind: ' . $kind);
            }

            $files = $this->entityManager->createQueryBuilder()
                ->select('f')
                ->from(ItineraryFile::class, 'f')
                ->leftJoin(
                    $itineraryJoin, 'itinerary', Join::WITH,
                    'f.itineraryTable = ' . $kindId . ' AND f.itineraryId = itinerary.id'
                )
                ->where('f.itineraryTable = ' . $kindId)
                ->andWhere($condition)
                ->getQuery()
                ->getResult();

            $this->logger->info($itineraryJoin . ' - found ' . count($files) . PHP_EOL);

            foreach ($files as $file) {
                $this->removeFile($file);
                $this->logger->info(' - ' . $file->getId() . ' - ' . $file->getFilename() . PHP_EOL);
                $list[$file->getId()] = $file->getFilename();
            }

            $this->entityManager->flush();
        }

        return $list;
    }

    private function generateStorageKey($userId, $content): string
    {
        return sprintf(
            '%s_%s_%s',
            self::STORAGE_KEY_PREFIX,
            $userId,
            hash('sha256', $content . '_' . StringHandler::getPseudoRandomString(64))
        );
    }
}
