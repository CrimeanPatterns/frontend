<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\SegmentIterSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapItem;
use AwardWallet\MainBundle\Timeline\SegmentMapSourceInterface;
use AwardWallet\MainBundle\Timeline\SegmentMapUtils;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

class RestaurantRepository extends EntityRepository implements ItineraryRepositoryInterface, SegmentMapSourceInterface, SegmentIterSourceInterface
{
    protected $kindEvent = [
        Restaurant::EVENT_RESTAURANT => 'E',
        Restaurant::EVENT_MEETING => 'U',
        Restaurant::EVENT_SHOW => 'U',
        Restaurant::EVENT_EVENT => 'U',
        Restaurant::EVENT_RAVE => 'U',
    ];

    public function RestaurantsSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT t.RestaurantID AS ID                              ,
			       'E'            AS Kind                            ,
			       t.EventType    AS Category                        ,
			       p.Name         AS ProviderName                    ,
			       COALESCE(p.ShortName, p.Name, t.Name)         AS ProviderNameMobile              ,
			       t.TravelPlanID                                    ,
			       0           AS Cancelled                          ,
			       t.StartDate AS StartDate                          ,
			       t.EndDate   AS EndDate                            ,
			       t.Hidden                                          ,
			       t.AccountID                                       ,
			       t.Parsed                                          ,
			       COALESCE(a.ProviderID, t.ProviderID) AS ProviderID,
			       COALESCE(a.UserID, t.UserID)         AS UserID    ,
			       t.UserAgentID                                     ,
			       t.Moved                                           ,
			       t.ConfNo AS ConfirmationNumber					 ,
			       t.ConfFields,
			       50 as SortIndex
			FROM   Restaurant t
			       LEFT OUTER JOIN Account a
			       ON     t.AccountID = a.AccountID
			       LEFT OUTER JOIN Provider p
			       ON     a.ProviderID = p.ProviderID 
			$filterStr			
		";
        $s = str_ireplace('[StartDate]', 't.StartDate', $s);

        return $s;
    }

    public function RestaurantsSourceSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT t.RestaurantID AS ID                              ,
			       'E'            AS Kind                            ,
                   concat('E.', t.RestaurantID) as SourceID          ,
                   t.MailDate
              FROM Restaurant t
			{$filterStr}
		";
        $s = str_ireplace('[StartDate]', 't.StartDate', $s);

        return $s;
    }

    public function kindEvent($eventType)
    {
        return $this->kindEvent[$eventType];
    }

    public function getPhones($tripId)
    {
        $restaurant = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Restaurant::class)->find($tripId);

        if (!$restaurant) {
            return null;
        }
        $geoTag = $restaurant->getGeotagid();
        $country = (isset($geoTag)) ? $geoTag->getCountry() : null;

        $result = [];

        if ($restaurant->getPhone() != null) {
            $result[] = [
                'name' => 'Phone',
                'phone' => $restaurant->getPhone(),
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
        $builder = $this->createQueryBuilder('restaurant');
        $builder->select('restaurant, provider, account, geoTag');
        $builder->where('restaurant.user = :user');
        $builder->setParameter('user', $user);

        if ($queryOptions->hasItems()) {
            if ($ids = SegmentMapUtils::filterIdsByType($queryOptions->getItems(), ['E'])) {
                $builder->andWhere('restaurant.id IN (:ids)');
                $builder->setParameter('ids', $ids);
            } else {
                return [];
            }
        }

        $userAgent = $queryOptions ? $queryOptions->getUserAgent() : null;

        if (!empty($userAgent)) {
            $builder->andWhere('restaurant.userAgent = :userAgent');
            $builder->setParameter('userAgent', $userAgent);
        } else {
            $builder->andWhere('restaurant.userAgent IS NULL');
        }

        $startDate = $queryOptions ? $queryOptions->getStartDate() : null;

        if (null !== $startDate) {
            $criteria = $builder->expr()->orX(
                'restaurant.startdate >= :startDate',
                'restaurant.enddate >= :startDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('startDate', $startDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasEndDate()) {
                $builder->orderBy('restaurant.startdate');
            }
        }

        $endDate = $queryOptions ? $queryOptions->getEndDate() : null;

        if (null !== $endDate) {
            $criteria = $builder->expr()->orX(
                'restaurant.startdate <= :endDate',
                'restaurant.enddate >= :endDate'
            );
            $builder->andWhere($criteria);
            $builder->setParameter('endDate', $endDate);

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasStartDate()) {
                $builder->orderBy('restaurant.startdate', 'DESC');
            }
        }

        if (!$queryOptions->isShowDeleted()) {
            $builder->andWhere('restaurant.hidden = FALSE');
        }

        $builder->leftJoin('restaurant.provider', 'provider');
        $builder->leftJoin('restaurant.account', 'account');
        $builder->leftJoin('restaurant.geotagid', 'geoTag');

        $segments = $builder->getQuery()->toIterable();

        /** @var $segment Restaurant */
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
                r.RestaurantID as id,
                r.StartDate as startDate,
                r.EndDate as endDate,
                r.Hidden as deleted,
                'E' as type,
                CONCAT('E.', r.RestaurantID) as shareId,
                gt.TimeZoneLocation as timezone
            FROM Restaurant r
            left join GeoTag gt on r.GeoTagID = gt.GeoTagID
            WHERE
                {$conditions}"
        );

        $utcTimezone = new \DateTimeZone('UTC');
        $stmt->execute($params);

        $result = [];

        foreach ($stmt->fetchAll(AbstractQuery::HYDRATE_ARRAY) as $row) {
            $timezoneObj = $row['timezone'] === null ?
                $utcTimezone :
                DateTimeUtils::timezoneFromString($row['timezone'], $utcTimezone);
            $row['deleted'] = (bool) $row['deleted'];
            $row['startDate'] = new \DateTime($row['startDate'], $timezoneObj);

            if (isset($row['endDate'])) {
                $row['endDate'] = new \DateTime($row['endDate'], $timezoneObj);
            }

            unset($row['timezone']);
            $result[] = $row;
        }

        return $result;
    }

    public function findMatchingCandidates(Usr $owner, $schemaEvent): array
    {
        if (!$schemaEvent instanceof SchemaEvent) {
            throw new \InvalidArgumentException("Excpected " . SchemaEvent::class . ", got " . get_class($schemaEvent));
        }
        $confirmationNumbers = null !== $schemaEvent->confirmationNumbers ? array_map(function (ConfNo $number) { return $number->number; }, $schemaEvent->confirmationNumbers) : [];

        if (null !== $schemaEvent->travelAgency && !empty($schemaEvent->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaEvent->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('it');
        $builder->where('it.user = :user');
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in('filterConfirmationNumber(it.confirmationNumber)', ':numbers'),
            $builder->expr()->in('filterConfirmationNumber(it.travelAgencyConfirmationNumbers)', ':numbers'),
            $builder->expr()->eq('it.startdate', ':startDate'),
            $builder->expr()->eq('it.address', ':address'),
            $builder->expr()->eq('it.enddate', ':endDate'),
            $builder->expr()->eq('it.name', ':eventName')
        ));
        $builder->setParameter("numbers", $confirmationNumbers);
        $builder->setParameter("address", $schemaEvent->address ? $schemaEvent->address->text : '');
        $builder->setParameter('startDate', $schemaEvent->startDateTime);
        $builder->setParameter('endDate', $schemaEvent->endDateTime);
        $builder->setParameter("eventName", $schemaEvent->eventName);

        return $builder->getQuery()->getResult();
    }

    public function getFutureCriteria(): Criteria
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->gt('t.startdate', new \DateTime()));

        return $criteria;
    }
}
