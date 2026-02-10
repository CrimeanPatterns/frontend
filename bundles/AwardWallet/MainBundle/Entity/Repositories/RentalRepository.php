<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentIterSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapItem;
use AwardWallet\MainBundle\Timeline\SegmentMapSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapUtils;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\ConfNo;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

class RentalRepository extends EntityRepository implements ItineraryRepositoryInterface, SegmentMapSourceInterface, SegmentIterSourceInterface
{
    public function RentalsSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT t.RentalID                AS ID                   ,
			       'L'                       AS Kind                 ,
			       0                         AS Category             ,
			       COALESCE(p.Name, tp.Name) AS ProviderName         ,
			       COALESCE(p.ShortName, p.Name, tp.Name) AS ProviderNameMobile   ,
			       t.TravelPlanID                                    ,
			       t.Cancelled                                       ,
			       t.PickupDatetime  AS StartDate                    ,
			       t.DropoffDatetime AS EndDate                      ,
			       t.Hidden                                          ,
			       t.AccountID                                       ,
			       t.Parsed                                          ,
			       COALESCE(a.ProviderID, t.ProviderID) AS ProviderID,
			       COALESCE(a.UserID, t.UserID)         AS UserID    ,
			       t.UserAgentID                                     ,
			       t.Moved                                           ,
			       t.Number AS ConfirmationNumber					 ,
			       t.ConfFields,
			       20 as SortIndex
			FROM   Rental t
			       LEFT OUTER JOIN Account a
			       ON     t.AccountID = a.AccountID
			       LEFT OUTER JOIN Provider p
			       ON     a.ProviderID = p.ProviderID
			       LEFT OUTER JOIN Provider tp
			       ON     t.ProviderID = tp.ProviderID 
			$filterStr
		";
        $s = str_ireplace('[StartDate]', 't.PickupDatetime', $s);

        return $s;
    }

    public function RentalsSourceSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT t.RentalID                AS ID                   ,
			       'L'                       AS Kind                 ,
                   concat('L.', t.RentalID) as SourceID        ,
                   t.MailDate
              FROM Rental t
			{$filterStr}
		";
        $s = str_ireplace('[StartDate]', 't.PickupDatetime', $s);

        return $s;
    }

    public function getPhones($tripId)
    {
        $rental = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Rental::class)->find($tripId);

        if (!$rental) {
            return null;
        }
        $pickupGeoTag = $rental->getPickupgeotagid();
        $pickupCountry = (isset($pickupGeoTag)) ? $pickupGeoTag->getCountry() : null;
        $dropoffGeoTag = $rental->getDropoffgeotagid();
        $dropoffCountry = (isset($dropoffGeoTag)) ? $dropoffGeoTag->getCountry() : null;

        $result = [];

        if ($rental->getPickupphone() != null) {
            $result[] = [
                'name' => 'Pickup phone',
                'phone' => $rental->getPickupphone(),
                'region' => $pickupCountry,
            ];
        }

        if ($rental->getDropoffphone() != null) {
            $result[] = [
                'name' => 'Dropoff phone',
                'phone' => $rental->getDropoffphone(),
                'region' => $dropoffCountry,
            ];
        }

        return $result;
    }

    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        return \iter\toArray($this->getTimelineItemsIter($user, $queryOptions));
    }

    public function getTimelineItemsIter(Usr $user, ?QueryOptions $queryOptions = null): iterable
    {
        $builder = $this->createQueryBuilder('rental');
        $builder->select('rental, provider, account, pickUpGeoTag, dropOffGeoTag');
        $builder->where('rental.user = :user');
        $builder->setParameter('user', $user);

        if ($queryOptions->hasItems()) {
            if ($ids = SegmentMapUtils::filterIdsByType($queryOptions->getItems(), ['PU', 'DO'])) {
                $builder->andWhere('rental.id IN (:ids)');
                $builder->setParameter('ids', $ids);
            } else {
                return [];
            }
        }

        if (!empty($userAgent = $queryOptions ? $queryOptions->getUserAgent() : null)) {
            $builder->andWhere('rental.userAgent = :userAgent');
            $builder->setParameter('userAgent', $userAgent);
        } else {
            $builder->andWhere('rental.userAgent IS NULL');
        }

        $startDate = $queryOptions ? $queryOptions->getStartDate() : null;

        if (null !== $startDate) {
            $criteria = $builder->expr()->orX(
                'rental.pickupdatetime >= :startDate',
                'rental.dropoffdatetime >= :startDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('startDate', $startDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasEndDate()) {
                $builder->orderBy('rental.pickupdatetime');
            }
        }

        $endDate = $queryOptions ? $queryOptions->getEndDate() : null;

        if (null !== $endDate) {
            $criteria = $builder->expr()->orX(
                'rental.pickupdatetime <= :endDate',
                'rental.dropoffdatetime <= :endDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('endDate', $endDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasStartDate()) {
                $builder->orderBy('rental.pickupdatetime', 'DESC');
            }
        }

        if (!$queryOptions->isShowDeleted()) {
            $builder->andWhere('rental.hidden = FALSE');
        }

        $builder->leftJoin('rental.provider', 'provider');
        $builder->leftJoin('rental.account', 'account');
        $builder->leftJoin('rental.pickupgeotagid', 'pickUpGeoTag');
        $builder->leftJoin('rental.dropoffgeotagid', 'dropOffGeoTag');

        $segments = $builder->getQuery()->toIterable();

        /** @var $segment Rental */
        foreach ($segments as $segment) {
            yield from $segment->getTimelineItems($user, $queryOptions);
        }
    }

    /**
     * @return SegmentMapItem[]
     */
    public function getTimelineMapItems(Usr $user, ?Useragent $useragent = null): array
    {
        $conditions = [
            'r.UserID = :userid',
        ];
        $params['userid'] = $user->getUserid();

        if ($useragent) {
            $conditions[] = 'r.UserAgentID = :useragentid';
            $params['useragentid'] = $useragent->getUseragentid();
        } else {
            $conditions[] = 'r.UserAgentID IS NULL';
        }

        $conditions = implode(' AND ', $conditions);

        $stmt = $this->_em->getConnection()->prepare("
              SELECT
                  r.RentalID as id,
                  r.PickupDatetime as startDate,
                  r.DropoffDatetime as endDate,
                  r.Hidden as deleted,
                  CONCAT('L.', r.RentalID) as shareId,
                  gtPickup.TimeZoneLocation as timezonePickup,
                  gtDropoff.TimeZoneLocation as timezoneDropoff
              FROM Rental r
              left join GeoTag gtPickup on r.PickupGeoTagID = gtPickup.GeoTagID
              left join GeoTag gtDropoff on r.DropoffGeoTagID = gtDropoff.GeoTagID
              WHERE
                  {$conditions}"
        );

        $stmt->execute($params);
        $utcTimezone = new \DateTimeZone('UTC');
        $result = [];

        foreach ($stmt->fetchAll(AbstractQuery::HYDRATE_SCALAR) as $row) {
            [$id, $startDate, $endDate, $deleted, $shareId, $timezonePickup, $timezoneDropoff] = $row;

            /** @var SegmentMapItem $pickup */
            $pickup = [];
            $pickup['id'] = $id;
            $pickup['type'] = 'PU';
            $pickup['startDate'] = new \DateTime(
                $startDate,
                $timezonePickup === null ?
                    $utcTimezone :
                    DateTimeUtils::timezoneFromString($timezonePickup, $utcTimezone)
            );
            $pickup['endDate'] = null;
            $pickup['deleted'] = (bool) $deleted;
            $pickup['shareId'] = $shareId;

            $result[] = $pickup;

            /** @var SegmentMapItem $dropoff */
            $dropoff = [];
            $dropoff['id'] = $id;
            $dropoff['type'] = 'DO';
            $dropoff['startDate'] = new \DateTime(
                $endDate,
                $timezoneDropoff === null ?
                    $utcTimezone :
                    DateTimeUtils::timezoneFromString($timezoneDropoff, $utcTimezone)
            );
            $dropoff['endDate'] = null;
            $dropoff['deleted'] = (bool) $deleted;
            $dropoff['shareId'] = $shareId;

            $result[] = $dropoff;
        }

        return $result;
    }

    public function findMatchingCandidates(Usr $owner, $schemaRental): array
    {
        if (!$schemaRental instanceof SchemaRental) {
            throw new \InvalidArgumentException("Excpected " . SchemaRental::class . ", got " . get_class($schemaRental));
        }
        $confirmationNumbers = null !== $schemaRental->confirmationNumbers ? array_map(function (ConfNo $number) { return $number->number; }, $schemaRental->confirmationNumbers) : [];

        if (null !== $schemaRental->travelAgency && !empty($schemaRental->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaRental->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('it');
        $builder->where('it.user = :user');
        $builder->setParameter('user', $owner);

        $filters = [
            $builder->expr()->in('filterConfirmationNumber(it.confirmationNumber)', ':numbers'),
            $builder->expr()->in('filterConfirmationNumber(it.travelAgencyConfirmationNumbers)', ':numbers'),
        ];
        $builder->setParameter("numbers", $confirmationNumbers);

        if ($schemaRental->pickup !== null) {
            if (null !== $schemaRental->pickup->address) {
                $filters[] = $builder->expr()->eq('it.pickuplocation', ':pickupLocation');
                $builder->setParameter("pickupLocation", $schemaRental->pickup->address->text);
            }

            if (null !== $schemaRental->pickup->localDateTime) {
                $filters[] = $builder->expr()->eq('it.pickupdatetime', ':pickupDate');
                $builder->setParameter('pickupDate', $schemaRental->pickup->localDateTime);
            }
        }

        if (null !== $schemaRental->dropoff) {
            if (null !== $schemaRental->dropoff->address) {
                $filters[] = $builder->expr()->eq('it.dropofflocation', ':dropoffLocation');
                $builder->setParameter("dropoffLocation", $schemaRental->dropoff->address->text);
            }

            if (null !== $schemaRental->dropoff->localDateTime) {
                $filters[] = $builder->expr()->eq('it.dropoffdatetime', ':dropoffDate');
                $builder->setParameter('dropoffDate', $schemaRental->dropoff->localDateTime);
            }
        }

        $builder->andWhere($builder->expr()->orX(...$filters));

        return $builder->getQuery()->getResult();
    }

    public function getFutureCriteria(): Criteria
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->gt('t.dropoffdatetime', new \DateTime()));

        return $criteria;
    }
}
