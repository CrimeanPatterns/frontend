<?php

namespace AwardWallet\MainBundle\Entity\Files;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * @ORM\Entity
 * @ORM\Table(name="ItineraryFile")
 * @ORM\HasLifecycleCallbacks
 */
class ItineraryFile extends AbstractFile
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="ItineraryFileID", type="integer", nullable=false)
     */
    protected ?int $id;

    /**
     * @ORM\Column(name="ItineraryTable", type="integer", nullable=false)
     */
    protected int $itineraryTable;

    /**
     * @ORM\Column(name="ItineraryID", type="integer", nullable=true)
     */
    protected ?int $itineraryId = null;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function getItineraryTable(): int
    {
        return $this->itineraryTable;
    }

    public function setItineraryTable(int $itineraryTable): self
    {
        $this->itineraryTable = $itineraryTable;

        return $this;
    }

    public function getItineraryId(): ?int
    {
        return $this->itineraryId;
    }

    public function setItineraryId(int $itineraryId): self
    {
        $this->itineraryId = $itineraryId;

        return $this;
    }

    public function getItinerary(): ?Itinerary
    {
        if (null === $this->getItineraryId()) {
            return null;
        }

        $kinds = array_flip(Itinerary::ITINERARY_KIND_TABLE);

        if (!array_key_exists($this->getItineraryTable(), $kinds)) {
            return null;
        }

        $itineraryId = $this->getItineraryId();

        switch ($kinds[$this->getItineraryTable()]) {
            case Itinerary::KIND_TRIP:
                return $this->entityManager->getRepository(Trip::class)->find($itineraryId);

            case Itinerary::KIND_RESERVATION:
                return $this->entityManager->getRepository(Reservation::class)->find($itineraryId);

            case Itinerary::KIND_RENTAL:
                return $this->entityManager->getRepository(Rental::class)->find($itineraryId);

            case Itinerary::KIND_RESTAURANT:
                return $this->entityManager->getRepository(Restaurant::class)->find($itineraryId);

            case Itinerary::KIND_PARKING:
                return $this->entityManager->getRepository(Parking::class)->find($itineraryId);
        }

        throw new \RuntimeException('Unknown kind table ' . $this->getItineraryTable());
    }

    /**
     * @ORM\PostLoad
     * @ORM\PostPersist
     */
    public function fetchEntityManager(LifecycleEventArgs $args)
    {
        $this->entityManager = $args->getObjectManager();
    }
}
