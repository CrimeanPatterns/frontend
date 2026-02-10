<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form;

use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Query\ItineraryChecker;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Saver
{
    private const TMP_USER_FILE_FORMAT = 'tmp_it_user_%d_%s';
    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    private ItineraryChecker $itineraryChecker;

    private EntityManagerInterface $entityManager;

    private ItineraryFileManager $itineraryFileManager;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        ItineraryChecker $itineraryChecker,
        EntityManagerInterface $entityManager,
        ItineraryFileManager $itineraryFileManager
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->itineraryChecker = $itineraryChecker;
        $this->entityManager = $entityManager;
        $this->itineraryFileManager = $itineraryFileManager;
    }

    public function save(Itinerary $itinerary, array $fileDescriptions)
    {
        $edit = !is_null($itinerary->getId());
        $itinerary->setModified(true);
        $itinerary->setUpdateDate(new \DateTime());

        if (($dub = $this->itineraryChecker->isUnique($itinerary)) !== true) {
            $duplicate = $dub->first();
            $this->entityManager->remove($duplicate);
            $this->entityManager->flush($duplicate);
        }

        if (!$edit) {
            $this->entityManager->persist($itinerary);
        }

        $this->entityManager->flush();
        $this->eventDispatcher->dispatch(new ItineraryUpdateEvent($itinerary));

        if (\count($fileDescriptions) > 0) {
            $this->saveFile($edit, $fileDescriptions, $itinerary);
        }

        if ($edit) {
            $this->logger->warning(sprintf('edited itinerary %s', $itinerary->getIdString()));
        } else {
            $this->logger->warning(sprintf('created itinerary %s', $itinerary->getIdString()));
        }
    }

    public function uploadFile(UploadedFile $file, Itinerary $itinerary): int
    {
        $this->itineraryFileManager->baseValidate($file);
        /** @var ItineraryFile $itFile */
        $itFile = $this->itineraryFileManager->saveUploadedFile($file, $itinerary->getUser(), ItineraryFile::class);
        $itFile->setItineraryTable(Itinerary::ITINERARY_KIND_TABLE[$itinerary->getKind()]);

        if (null !== $itinerary->getId()) {
            $itFile->setItineraryId($itinerary->getId());
            $itinerary->addFile($itFile);
        }
        $this->entityManager->persist($itFile);
        $this->entityManager->flush();

        return $itFile->getId();
    }

    public function tmpUploadFile(UploadedFile $file, Itinerary $itinerary): array
    {
        $fileId = $this->uploadFile($file, $itinerary);

        return [
            'id' => $fileId,
            'fileName' => $file->getClientOriginalName(),
            'fileSize' => $file->getSize(),
            'description' => '',
            'uploadDate' => date('c'),
        ];
    }

    private function saveFile(bool $isEdit, array $fileDescriptions, Itinerary $itinerary): void
    {
        if (!$isEdit) {
            foreach ($fileDescriptions as $fileId => $description) {
                /** @var ItineraryFile $file */
                $file = $this->entityManager->getRepository(ItineraryFile::class)->findOneBy([
                    'id' => (int) $fileId,
                    'itineraryTable' => Itinerary::ITINERARY_KIND_TABLE[$itinerary->getKind()],
                ]);

                if (null === $file->getItineraryId()) {
                    $file->setItineraryId($itinerary->getId());
                    $this->entityManager->persist($file);
                }
            }
            $this->entityManager->flush();
        }

        $this->itineraryFileManager->updateItineraryFilesDescriptions($fileDescriptions);
        $this->entityManager->flush();
    }
}
