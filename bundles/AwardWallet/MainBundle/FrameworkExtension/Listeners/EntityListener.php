<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

class EntityListener
{
    private EntityManagerInterface $entityManager;
    private ItineraryFileManager $itineraryFileManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ItineraryFileManager $itineraryFileManager
    ) {
        $this->entityManager = $entityManager;
        $this->itineraryFileManager = $itineraryFileManager;
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Itinerary && count($entity->getFiles())) {
            foreach ($entity->getFiles() as $file) {
                $this->itineraryFileManager->removeFile($file);
            }
            $this->entityManager->flush();
        }
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
    }
}
