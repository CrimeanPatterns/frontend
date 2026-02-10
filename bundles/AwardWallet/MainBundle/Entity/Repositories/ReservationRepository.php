<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentIterSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapItem;
use AwardWallet\MainBundle\Timeline\SegmentMapSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapUtils;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

class ReservationRepository extends EntityRepository implements ItineraryRepositoryInterface, SegmentMapSourceInterface, SegmentIterSourceInterface
{
    public function ReservationsSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT t.ReservationID           AS ID                   ,
			       'R'                       AS Kind                 ,
			       0                         AS Category             ,
			       COALESCE(p.Name, tp.Name) AS ProviderName         ,
			       COALESCE(p.ShortName, p.Name, tp.Name) AS ProviderNameMobile         ,
			       t.TravelPlanID                                    ,
			       t.Cancelled                                       ,
			       t.CheckInDate  AS StartDate                       ,
			       t.CheckOutDate AS EndDate                         ,
			       t.Hidden                                          ,
			       t.AccountID                                       ,
			       t.Parsed                                          ,
			       COALESCE(a.ProviderID, t.ProviderID) AS ProviderID,
			       COALESCE(a.UserID, t.UserID)         AS UserID    ,
			       t.UserAgentID                                     ,
			       t.Moved                                           ,
			       t.ConfirmationNumber								 ,
			       t.ConfFields,
			       40 as SortIndex
			FROM   Reservation t
			       LEFT OUTER JOIN Account a
			       ON     t.AccountID = a.AccountID
			       LEFT OUTER JOIN Provider p
			       ON     a.ProviderID = p.ProviderID
			       LEFT OUTER JOIN Provider tp
			       ON     t.ProviderID = tp.ProviderID 
			$filterStr			
		";
        $s = str_ireplace('[StartDate]', 't.CheckInDate', $s);

        return $s;
    }

    public function ReservationsSourceSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT t.ReservationID           AS ID                   ,
			       'R'                       AS Kind                 ,
                   concat('R.', t.ReservationID) as SourceID          ,
                   t.MailDate
              FROM Reservation t
			{$filterStr}
		";
        $s = str_ireplace('[StartDate]', 't.CheckInDate', $s);

        return $s;
    }

    public function getPhones($tripId)
    {
        $reservation = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class)->find($tripId);

        if (!$reservation) {
            return null;
        }
        $geoTag = $reservation->getGeotagid();
        $country = (isset($geoTag)) ? $geoTag->getCountry() : null;

        $result = [];

        if ($reservation->getPhone() != null) {
            $result[] = [
                'name' => 'Phone',
                'phone' => $reservation->getPhone(),
                'region' => $country,
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
        $builder = $this->createQueryBuilder('reservation');
        $builder->select('reservation, geoTag, provider, account');
        $builder->where('reservation.user = :user');
        $builder->setParameter('user', $user);

        if ($queryOptions->hasItems()) {
            if ($ids = SegmentMapUtils::filterIdsByType($queryOptions->getItems(), ['CI', 'CO'])) {
                $builder->andWhere('reservation.id IN (:ids)');
                $builder->setParameter('ids', $ids);
            } else {
                return [];
            }
        }

        if (!empty($userAgent = $queryOptions ? $queryOptions->getUserAgent() : null)) {
            $builder->andWhere('reservation.userAgent = :userAgent');
            $builder->setParameter('userAgent', $userAgent);
        } else {
            $builder->andWhere('reservation.userAgent IS NULL');
        }

        $startDate = $queryOptions ? $queryOptions->getStartDate() : null;

        if (null !== $startDate) {
            $criteria = $builder->expr()->orX(
                'reservation.checkindate >= :startDate',
                'reservation.checkoutdate >= :startDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('startDate', $startDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasEndDate()) {
                $builder->orderBy('reservation.checkindate');
            }
        }
        $endDate = $queryOptions ? $queryOptions->getEndDate() : null;

        if (null !== $endDate) {
            $criteria = $builder->expr()->orX(
                'reservation.checkindate <= :endDate',
                'reservation.checkoutdate <= :endDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('endDate', $endDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasStartDate()) {
                $builder->orderBy('reservation.checkindate', 'desc');
            }
        }

        if (!$queryOptions->isShowDeleted()) {
            $builder->andWhere('reservation.hidden = FALSE');
        }

        $builder->leftJoin('reservation.geotagid', 'geoTag');
        $builder->leftJoin('reservation.provider', 'provider');
        $builder->leftJoin('reservation.account', 'account');

        $segments = $builder->getQuery()->toIterable();

        /** @var $segment Reservation */
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
                r.ReservationID as id,
                r.CheckInDate as startDate,
                r.CheckOutDate as endDate,
                r.Hidden as deleted,
                CONCAT('R.', r.ReservationID) as shareId,
                gt.TimeZoneLocation as timezone
            FROM Reservation r
            left join GeoTag gt on r.GeoTagID = gt.GeoTagID
            WHERE
                {$conditions}"
        );

        $stmt->execute($params);

        $utcTimezone = new \DateTimeZone('UTC');
        $result = [];

        foreach ($stmt->fetchAll(AbstractQuery::HYDRATE_SCALAR) as $row) {
            [$id, $startDate, $endDate, $deleted, $shareId, $timezone] = $row;
            $timezoneObj = null === $timezone ?
                $utcTimezone :
                DateTimeUtils::timezoneFromString($timezone, $utcTimezone);
            /** @var SegmentMapItem $checkin */
            $checkin = [];
            $checkin['id'] = $id;
            $checkin['type'] = 'CI';
            $checkin['startDate'] = new \DateTime($startDate, $timezoneObj);
            $checkin['endDate'] = null;
            $checkin['deleted'] = (bool) $deleted;
            $checkin['shareId'] = $shareId;

            $result[] = $checkin;

            /** @var SegmentMapItem $checkout */
            $checkout = [];
            $checkout['id'] = $id;
            $checkout['type'] = 'CO';
            $checkout['startDate'] = new \DateTime($endDate, $timezoneObj);
            $checkout['endDate'] = null;
            $checkout['deleted'] = (bool) $deleted;
            $checkout['shareId'] = $shareId;

            $result[] = $checkout;
        }

        return $result;
    }

    public function findMatchingCandidates(Usr $owner, $schemaReservation): array
    {
        if (!$schemaReservation instanceof SchemaReservation) {
            throw new \InvalidArgumentException("Excpected " . SchemaReservation::class . ", got " . get_class($schemaReservation));
        }
        $confirmationNumbers = null !== $schemaReservation->confirmationNumbers ? array_map(function (ConfNo $number) { return $number->number; }, $schemaReservation->confirmationNumbers) : [];

        if (null !== $schemaReservation->travelAgency && !empty($schemaReservation->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaReservation->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('reservation');
        $builder->where('reservation.user = :user');
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in("filterConfirmationNumber(reservation.confirmationNumber)", ":numbers"),
            $builder->expr()->in('filterConfirmationNumber(reservation.travelAgencyConfirmationNumbers)', ":numbers"),
            $builder->expr()->eq('reservation.hotelname', ':hotelName'),
            $builder->expr()->andX(
                $builder->expr()->gte('reservation.checkindate', ':checkinDateFrom'),
                $builder->expr()->lt('reservation.checkindate', ':checkinDateBefore')
            )
        ));
        $builder->setParameter("numbers", $confirmationNumbers);
        $builder->setParameter("hotelName", $schemaReservation->hotelName);
        $builder->setParameter('checkinDateFrom', date("Y-m-d", strtotime($schemaReservation->checkInDate)));
        $builder->setParameter('checkinDateBefore', date("Y-m-d", strtotime("+1 day", strtotime($schemaReservation->checkInDate))));

        return $builder->getQuery()->getResult();
    }

    public function getFutureCriteria(): Criteria
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->gt('t.checkoutdate', new \DateTime()));

        return $criteria;
    }
}
