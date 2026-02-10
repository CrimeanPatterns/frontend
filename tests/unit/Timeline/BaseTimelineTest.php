<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\JsonNormalizer;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BaseTimelineTest extends BaseUserTest
{
    /**
     * @var Manager
     */
    protected $manager;
    protected ?JsonNormalizer $jsonNormalizer;

    public function _before()
    {
        parent::_before();
        $this->manager = $this->container->get(Manager::class);
        $this->jsonNormalizer = $this->getModule('JsonNormalizer');
    }

    public function _after()
    {
        $this->manager = null;
        $this->jsonNormalizer = null;

        parent::_after();
    }

    /**
     * @return Itinerary[]
     */
    protected function loadHomogeneousEntities(): array
    {
        $entities = [];

        foreach (['Trip', 'Reservation', 'Rental', 'Parking', 'Restaurant'] as $entityName) {
            $entities =
                $this->em
                ->getRepository(Itinerary::getItineraryClass(ucfirst($entityName)))
                ->findBy(['user' => $this->user->getId()]);

            if ($entities) {
                break;
            }
        }

        return $entities;
    }

    /**
     * @param Itinerary[] $entities
     */
    protected function getCtxMergeData(array $entities): array
    {
        if (!$entities) {
            return [];
        }

        $ctxMerge = [];

        if ($entities[0] instanceof Trip) {
            /** @var Tripsegment $tripSegment */
            foreach (
                it($entities)
                ->flatMap(fn (Trip $trip) => $trip->getSegments())
                ->usort(fn (Tripsegment $itA, Tripsegment $itB) => $itA->getId() <=> $itB->getId())
                ->toArray() as $tsIdx => $tripSegment
            ) {
                $trip = $tripSegment->getTripid();
                $ctxMerge["TS_ID.{$tsIdx}"] = 'T.' . $tripSegment->getId();
                $ctxMerge["IT_ID.{$tsIdx}"] = $trip->getIdString();
                $ctxMerge["COUNT_ID.{$tsIdx}"] = $trip->getIdString();
                $ctxMerge["SHARE_CODE.{$tsIdx}"] = $trip->getEncodedShareCode();
            }
        } else {
            /** @var AbstractItinerary $tlItinerary */
            foreach (
                it($entities)
                ->usort(fn (Itinerary $itA, Itinerary $itB) => $itA->getId() <=> $itB->getId())
                ->flatMap(fn (Itinerary $it) => $it->getTimelineItems($this->user))
                ->toArray() as $idx => $tlItinerary
            ) {
                $source = $tlItinerary->getSource();
                $ctxMerge["IT_ID.{$idx}"] = $tlItinerary->getId();
                $ctxMerge["SHARE_CODE.{$idx}"] = $source->getEncodedShareCode();
                $ctxMerge["COUNT_ID.{$idx}"] = $source->getIdString();
            }
        }

        return $ctxMerge;
    }

    protected function getDefaultDesktopQueryOptions()
    {
        return (new QueryOptions())
            ->setFormat(ItemFormatterInterface::DESKTOP)
            ->setWithDetails(false);
    }
}
