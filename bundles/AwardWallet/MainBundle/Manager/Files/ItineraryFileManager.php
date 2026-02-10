<?php

namespace AwardWallet\MainBundle\Manager\Files;

use AwardWallet\MainBundle\Entity\Files\ItineraryFile;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Service\Storage\ItineraryFileStorage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ItineraryFileManager extends FileManager
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ItineraryFileStorage $itineraryFileStorage,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($entityManager, $itineraryFileStorage, $translator, $logger, $authorizationChecker);
    }

    public function getFiles(string $kindTable, int $itineraryId): ArrayCollection
    {
        $table = Itinerary::ITINERARY_KIND_TABLE[$kindTable];
        $result = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(ItineraryFile::class, 'f')
            ->where('f.itineraryTable = :table')->setParameter('table', $table)
            ->andWhere('f.itineraryId = :itineraryId')->setParameter('itineraryId', $itineraryId)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    public function updateItineraryFilesDescriptions(array $files): void
    {
        $fileRepository = $this->entityManager->getRepository(ItineraryFile::class);

        foreach ($files as $fileId => $description) {
            if (!is_numeric($fileId) || !is_string($description)) {
                continue;
            }

            $file = $fileRepository->find($fileId);

            if (null !== $file && $this->authorizationChecker->isGranted('EDIT', $file)) {
                $file->setDescription($description);
                $this->entityManager->persist($file);
            }
        }
    }
}
