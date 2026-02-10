<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentIterSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapItem;
use AwardWallet\MainBundle\Timeline\SegmentMapSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapUtils;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Parking as SchemaParking;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

class ParkingRepository extends EntityRepository implements ItineraryRepositoryInterface, SegmentMapSourceInterface, SegmentIterSourceInterface
{
    public function ParkingsSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
            SELECT t.ParkingID               AS ID,
                   'P'                       AS Kind,
                   0                         AS Category,
                   COALESCE(p.Name, tp.Name) AS ProviderName,
                   COALESCE(p.ShortName, p.Name, tp.Name) AS ProviderNameMobile,
                   t.TravelPlanID,
                   t.Cancelled,
                   t.StartDatetime  AS StartDate,
                   t.EndDatetime    AS EndDate,
                   t.Hidden,
                   t.AccountID,
                   t.Parsed,
                   COALESCE(a.ProviderID, t.ProviderID) AS ProviderID,
                   COALESCE(a.UserID, t.UserID)         AS UserID,
                   t.UserAgentID,
                   t.Moved,
                   t.Number AS ConfirmationNumber,
                   t.ConfFields,
                   60 as SortIndex
            FROM   Parking t
                   LEFT OUTER JOIN Account a
                   ON     t.AccountID = a.AccountID
                   LEFT OUTER JOIN Provider p
                   ON     a.ProviderID = p.ProviderID
                   LEFT OUTER JOIN Provider tp
                   ON     t.ProviderID = tp.ProviderID 
            $filterStr
        ";
        $s = str_ireplace('[StartDate]', 't.StartDatetime', $s);

        return $s;
    }

    public function ParkingsSourceSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
            SELECT t.ParkingID               AS ID,
                   'P'                       AS Kind,
                   concat('P.', t.ParkingID) AS SourceID,
                   t.MailDate
              FROM Parking t
            {$filterStr}
        ";
        $s = str_ireplace('[StartDate]', 't.StartDatetime', $s);

        return $s;
    }

    public function getPhones($tripId)
    {
        /** @var Parking $parking */
        $parking = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Parking::class)->find($tripId);

        if (!$parking) {
            return null;
        }
        $geoTag = $parking->getGeoTagID();
        $country = (isset($geoTag)) ? $geoTag->getCountry() : null;

        if ($parking->getPhone() != null) {
            return [
                'name' => 'Phone',
                'phone' => $parking->getPhone(),
                'region' => $country,
            ];
        }

        return [];
    }

    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
    {
        return \iter\toArray($this->getTimelineItemsIter($user, $queryOptions));
    }

    public function getTimelineItemsIter(Usr $user, ?QueryOptions $queryOptions = null): iterable
    {
        $builder = $this->createQueryBuilder('parking');
        $builder->select('parking, provider, account, geoTag');
        $builder->where('parking.user = :user');
        $builder->setParameter('user', $user);

        if ($queryOptions->hasItems()) {
            if ($ids = SegmentMapUtils::filterIdsByType($queryOptions->getItems(), ['PS', 'PE'])) {
                $builder->andWhere('parking.id IN (:ids)');
                $builder->setParameter('ids', $ids);
            } else {
                return [];
            }
        }

        if (!empty($userAgent = $queryOptions ? $queryOptions->getUserAgent() : null)) {
            $builder->andWhere('parking.userAgent = :userAgent');
            $builder->setParameter('userAgent', $userAgent);
        } else {
            $builder->andWhere('parking.userAgent IS NULL');
        }

        $startDate = $queryOptions ? $queryOptions->getStartDate() : null;

        if (null !== $startDate) {
            $criteria = $builder->expr()->orX(
                'parking.startdatetime >= :startDate',
                'parking.enddatetime >= :startDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('startDate', $startDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasEndDate()) {
                $builder->orderBy('parking.startdatetime');
            }
        }

        $endDate = $queryOptions ? $queryOptions->getEndDate() : null;

        if (null !== $endDate) {
            $criteria = $builder->expr()->orX(
                'parking.startdatetime <= :endDate',
                'parking.enddatetime <= :endDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('endDate', $endDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasStartDate()) {
                $builder->orderBy('parking.startdatetime', 'DESC');
            }
        }

        if (!$queryOptions->isShowDeleted()) {
            $builder->andWhere('parking.hidden = FALSE');
        }

        $builder->leftJoin('parking.provider', 'provider');
        $builder->leftJoin('parking.account', 'account');
        $builder->leftJoin('parking.geotagid', 'geoTag');

        $segments = $builder->getQuery()->toIterable();

        /** @var $segment Parking */
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
            'p.UserID = :userid',
        ];
        $params['userid'] = $user->getUserid();

        if ($useragent) {
            $conditions[] = 'p.UserAgentID = :useragentid';
            $params['useragentid'] = $useragent->getUseragentid();
        } else {
            $conditions[] = 'p.UserAgentID IS NULL';
        }

        $conditions = implode(' AND ', $conditions);

        $stmt = $this->_em->getConnection()->prepare(
            "
              SELECT
                  p.ParkingID as id,
                  p.StartDatetime as startDate,
                  p.EndDatetime as endDate,
                  p.Hidden as deleted,
                  CONCAT('P.', p.ParkingID) as shareId,
                  gt.TimeZoneLocation as timezone
              FROM Parking p
              left join GeoTag gt on p.GeoTagID = gt.GeoTagID
              WHERE
                  {$conditions}"
        );

        $stmt->execute($params);

        $result = [];
        $utcTimezone = new \DateTimeZone('UTC');

        foreach ($stmt->fetchAll(AbstractQuery::HYDRATE_SCALAR) as $row) {
            [$id, $startDate, $endDate, $deleted, $shareId, $timezone] = $row;
            $timezoneObj = null === $timezone ?
                $utcTimezone :
                DateTimeUtils::timezoneFromString($timezone);
            /** @var SegmentMapItem $start */
            $start = [];
            $start['id'] = $id;
            $start['type'] = 'PS';
            $start['startDate'] = new \DateTime($startDate, $timezoneObj);
            $start['endDate'] = null;
            $start['deleted'] = (bool) $deleted;
            $start['shareId'] = $shareId;

            $result[] = $start;

            /** @var SegmentMapItem $end */
            $end = [];
            $end['id'] = $id;
            $end['type'] = 'PE';
            $end['startDate'] = new \DateTime($endDate, $timezoneObj);
            $end['endDate'] = null;
            $end['deleted'] = (bool) $deleted;
            $end['shareId'] = $shareId;

            $result[] = $end;
        }

        return $result;
    }

    public function findMatchingCandidates(Usr $owner, $schemaParking): array
    {
        if (!$schemaParking instanceof SchemaParking) {
            throw new \InvalidArgumentException("Excpected " . SchemaParking::class . ", got " . get_class($schemaParking));
        }
        $confirmationNumbers = null !== $schemaParking->confirmationNumbers ? array_map(function (ConfNo $number) { return $number->number; }, $schemaParking->confirmationNumbers) : [];

        if (null !== $schemaParking->travelAgency && !empty($schemaParking->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaParking->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('it');
        $builder->where('it.user = :user');
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in('filterConfirmationNumber(it.confirmationNumber)', ':numbers'),
            $builder->expr()->in('filterConfirmationNumber(it.travelAgencyConfirmationNumbers)', ':numbers'),
            $builder->expr()->eq('it.startdatetime', ':startDate'),
            $builder->expr()->eq('it.location', ':location'),
            $builder->expr()->eq('it.enddatetime', ':endDate')
        ));
        $builder->setParameter("numbers", $confirmationNumbers);
        $builder->setParameter("location", (null !== $schemaParking->address) ? ($schemaParking->address->text ?? '') : '');
        $builder->setParameter('startDate', $schemaParking->startDateTime);
        $builder->setParameter('endDate', $schemaParking->endDateTime);

        return $builder->getQuery()->getResult();
    }

    public function findFutureItinerariesByAccount(Account $account, ?bool $parsed = null): array
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('account', $account));
        $criteria->andWhere(Criteria::expr()->gt('enddatetime', new \DateTime()));

        if ($parsed !== null) {
            $criteria->andWhere(Criteria::expr()->eq('parsed', $parsed));
        }

        return $this->matching($criteria)->toArray();
    }

    public function getFutureCriteria(): Criteria
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->gt('t.enddatetime', new \DateTime()));

        return $criteria;
    }
}
