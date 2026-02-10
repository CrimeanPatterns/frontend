<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\FlightStats\AirlineConverter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\FlightWithStatus;
use AwardWallet\MainBundle\Timeline\Item\ItineraryInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentIterSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapUtils;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

class TripsegmentRepository extends EntityRepository implements SegmentIterSourceInterface
{
    /**
     * @var AirlineConverter
     */
    private $airlineConverter;

    public function findActualSegments($limit = 5000)
    {
        $criteria = new Criteria();
        $criteria->where(
            Criteria::expr()->andX(
                Criteria::expr()->orX(
                    Criteria::expr()->isNull('flightnumber'),
                    Criteria::expr()->eq('flightnumber', 'n/a')
                ),
                Criteria::expr()->gte('arrdate', new \DateTime()),
                Criteria::expr()->neq('arrcode', null),
                Criteria::expr()->neq('depcode', null),
                Criteria::expr()->neq('arrcode', ''),
                Criteria::expr()->neq('depcode', '')
            )
        );
        $query = $this
            ->createQueryBuilder('segment')
            ->leftJoin('segment.tripid', 'trip')
            ->addCriteria($criteria)
            ->andWhere('trip.cancelled = false')
            ->andWhere('trip.hidden = false')
            ->andWhere('trip.modified = false')
            ->setMaxResults($limit)
            ->getQuery();

        return $query->getResult();
    }

    public function findByTripAlertsFlight(FlightWithStatus $flightWithStatus): array
    {
        $iata = $flightWithStatus->getBookedAirlineIataCode();

        if (empty($iata)) {
            $iata = $this->airlineConverter->FSCodeToIata($flightWithStatus->getBookedAirlineCode());
        }

        $builder = $this->createQueryBuilder('tripSegment');
        $builder->select('tripSegment, trip');
        $builder->join('tripSegment.tripid', 'trip');
        $builder->join('trip.provider', 'provider');
        $builder->leftJoin('tripSegment.airline', 'airline');
        $builder->where($builder->expr()->orX('provider.IATACode = :iataCode', 'airline.code = :iataCode'));
        $builder->andWhere($builder->expr()->orX(
            "tripSegment.flightNumber = :flightNumber",
            "tripSegment.flightNumber = :variation1",
            "tripSegment.flightNumber = :variation2",
            "tripSegment.flightNumber = :variation3",
            "tripSegment.flightNumber = :variation4",
            "tripSegment.flightNumber = :variation5",
            "tripSegment.flightNumber = :variation6",
            "tripSegment.flightNumber = :variation7",
            "tripSegment.flightNumber = :variation8"
        ));
        $builder->andWhere('tripSegment.depcode = :depCode');
        $builder->andWhere('tripSegment.scheduledDepDate = :depDate');
        $builder->setParameters([
            'flightNumber' => $flightWithStatus->getFlightNumber(),
            'iataCode' => $iata,
            'variation1' => sprintf('%04s', $flightWithStatus->getFlightNumber()),
            'variation2' => sprintf('%03s', $flightWithStatus->getFlightNumber()),
            'variation3' => "{$iata}{$flightWithStatus->getFlightNumber()}",
            'variation4' => "{$iata} {$flightWithStatus->getFlightNumber()}",
            'variation5' => sprintf('%s%04s', $iata, $flightWithStatus->getFlightNumber()),
            'variation6' => sprintf('%s%03s', $iata, $flightWithStatus->getFlightNumber()),
            'variation7' => sprintf('%s%02s', $iata, $flightWithStatus->getFlightNumber()),
            'variation8' => sprintf('%02s', $flightWithStatus->getFlightNumber()),
            'depCode' => $flightWithStatus->getDeparture()->getAirportCode(),
            'depDate' => (new \DateTime($flightWithStatus->getDeparture()->getDateTime()))->format('Y-m-d H:i:s'),
        ]);

        return $builder->getQuery()->getResult();
    }

    /**
     * @return array<ItineraryInterface>
     */
    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        return \iter\toArray($this->getTimelineItemsIter($user, $queryOptions));
    }

    /**
     * @return iterable<ItineraryInterface>
     */
    public function getTimelineItemsIter(Usr $user, ?QueryOptions $queryOptions = null): iterable
    {
        $builder = $this->createQueryBuilder('tripSegment');
        $builder->select('tripSegment, trip, depGeoTag, arrGeoTag, provider, account');

        $builder->where('trip.user = :user');
        $builder->setParameter('user', $user);

        if ($queryOptions->hasItems()) {
            if ($ids = SegmentMapUtils::filterIdsByType($queryOptions->getItems(), 'T')) {
                $builder->andWhere('tripSegment.tripsegmentid IN (:ids)');
                $builder->setParameter('ids', $ids);
            } else {
                return [];
            }
        }

        if (!empty($userAgent = $queryOptions ? $queryOptions->getUserAgent() : null)) {
            $builder->andWhere('trip.userAgent = :userAgent');
            $builder->setParameter('userAgent', $userAgent);
        } else {
            $builder->andWhere('trip.userAgent IS NULL');
        }

        $startDate = $queryOptions ? $queryOptions->getStartDate() : null;

        if (null !== $startDate) {
            $criteria = $builder->expr()->orX(
                'tripSegment.depdate >= :startDate',
                'tripSegment.arrdate >= :startDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('startDate', $startDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasEndDate()) {
                $builder->orderBy('tripSegment.depdate', 'ASC');
            }
        }

        $endDate = $queryOptions ? $queryOptions->getEndDate() : null;

        if (null !== $endDate) {
            $criteria = $builder->expr()->orX(
                'tripSegment.arrdate <= :endDate',
                'tripSegment.depdate <= :endDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('endDate', $endDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasStartDate()) {
                $builder->orderBy('tripSegment.depdate', 'DEC');
            }
        }

        if (!$queryOptions->isShowDeleted()) {
            $builder->andWhere('trip.hidden = FALSE');
            $builder->andWhere('tripSegment.hidden = FALSE');
        }

        $builder->join('tripSegment.tripid', 'trip');
        $builder->leftJoin('trip.provider', 'provider');
        $builder->leftJoin('trip.account', 'account');
        $builder->leftJoin('tripSegment.depgeotagid', 'depGeoTag');
        $builder->leftJoin('tripSegment.arrgeotagid', 'arrGeoTag');

        $segments = $builder->getQuery()->toIterable();
        $tripIdMap = [];

        /** @var $segment Tripsegment */
        foreach ($segments as $segment) {
            $tripIdMap[$segment->getTripid()->getId()] = true;

            yield from $segment->getTimelineItems($user, $queryOptions);
        }

        if ($tripIdMap) {
            $this->getEntityManager()->createQueryBuilder()
                ->select('segments, partial t.{id}')
                ->from(Trip::class, 't')
                ->leftJoin('t.segments', 'segments')
                ->where('t.id IN (:tripIds)')
                ->setParameter('tripIds', \array_keys($tripIdMap))
                ->getQuery()
                ->getResult();
        }
    }

    public function setAirlineConverter(AirlineConverter $airlineConverter): void
    {
        $this->airlineConverter = $airlineConverter;
    }

    /**
     * @return Tripsegment[]
     */
    public function findMatchingCandidatesForFlight(Usr $owner, $schemaSegment): array
    {
        $builder = $this->createQueryBuilder('segment');
        $builder->join('segment.tripid', 'trip');
        $builder->where('trip.user = :user');
        $builder->setParameter('user', $owner);

        $conditions = [];

        if (null !== $schemaSegment->marketingCarrier->confirmationNumber) {
            $conditions[] = $builder->expr()->eq('segment.marketingAirlineConfirmationNumber', ':marketingAirlineConfirmationNumber');
            $builder->setParameter('marketingAirlineConfirmationNumber', $schemaSegment->marketingCarrier->confirmationNumber);
        }

        if (null !== $schemaSegment->operatingCarrier && null !== $schemaSegment->operatingCarrier->confirmationNumber) {
            $conditions[] = $builder->expr()->eq('segment.operatingAirlineConfirmationNumber', ':operatingAirlineConfirmationNumber');
            $builder->setParameter('operatingAirlineConfirmationNumber', $schemaSegment->operatingCarrier->confirmationNumber);
        }

        if (empty($conditions)) {
            return [];
        }
        $builder->andWhere($builder->expr()->orX(...$conditions));

        return $builder->getQuery()->getResult();
    }
}
