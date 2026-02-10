<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Trip as TripEntity;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * Class used to get buses, trains, cruises, etc.
 */
class Trip implements TripDataSourceInterface
{
    use ToolsTrait;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getData(Owner $owner, \DateTime $startDate, \DateTime $endDate): array
    {
        [$userId, $agentId] = self::getUserIds($owner);
        $trips = $this->getQuery($userId, $startDate, $endDate, $agentId)
            ->getResult(Query::HYDRATE_ARRAY);
        $result = [];

        foreach ($trips as $trip) {
            $category = TripEntity::CATEGORY_NAMES[$trip['category']];
            $model = new TripModel(
                $trip['tripsegmentid'],
                $trip['providerName'],
                $trip['depdate'],
                $trip['arrdate'],
                (new Marker($trip['depLat'], $trip['depLng'], $trip['depTimezone'], strtolower($category)))
                    ->setCity($trip['depCityName'])
                    ->setCountry($trip['depCountryName'])
                    ->setStateCode($trip['depStateCode'])
                    ->setCountryCode($trip['depCountryCode'])
                    ->setAirCode($trip['depcode'])
                    ->setLocationName($trip['depname']),
                (new Marker($trip['arrLat'], $trip['arrLng'], $trip['arrTimezone'], strtolower($category)))
                    ->setCity($trip['arrCityName'])
                    ->setCountry($trip['arrCountryName'])
                    ->setStateCode($trip['arrStateCode'])
                    ->setCountryCode($trip['arrCountryCode'])
                    ->setAirCode($trip['arrcode'])
                    ->setLocationName($trip['arrname']),
                TripEntity::SEGMENT_MAP
            );
            $model->setId($trip['tripId']);

            $result[] = $model;
        }

        return $result;
    }

    private function getQuery(int $userId, \DateTime $startDate, \DateTime $endDate, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                'tr.id AS tripId',
                'tr.category',
                'COALESCE(se.airlineName, pr.shortname, \'\') AS providerName',
                'se.tripsegmentid',
                'se.depcode',
                'se.depname',
                'se.depdate',
                'geotagDep.city AS depCityName',
                'geotagDep.country AS depCountryName',
                'geotagDep.stateCode AS depStateCode',
                'geotagDep.countryCode AS depCountryCode',
                'geotagDep.lat AS depLat',
                'geotagDep.lng AS depLng',
                'geotagDep.timeZoneLocation AS depTimezone',
                'se.arrcode',
                'se.arrname',
                'se.arrdate',
                'geotagArr.city AS arrCityName',
                'geotagArr.country AS arrCountryName',
                'geotagArr.stateCode AS arrStateCode',
                'geotagArr.countryCode AS arrCountryCode',
                'geotagArr.lat AS arrLat',
                'geotagArr.lng AS arrLng',
                'geotagArr.timeZoneLocation AS arrTimezone',
            ])
            ->from(TripEntity::class, 'tr');

        $query = $queryBuilder
            ->leftJoin('tr.segments', 'se')
            ->leftJoin('tr.provider', 'pr')
            ->innerJoin('se.depgeotagid', 'geotagDep')
            ->innerJoin('se.arrgeotagid', 'geotagArr')
            ->where('tr.user = :userId', 'tr.hidden = 0', 'tr.cancelled = 0', 'se.hidden = 0')
            ->andWhere($queryBuilder->expr()->in('tr.category', [
                TripEntity::CATEGORY_BUS,
                TripEntity::CATEGORY_TRAIN,
                TripEntity::CATEGORY_CRUISE,
                TripEntity::CATEGORY_FERRY,
                TripEntity::CATEGORY_TRANSFER,
            ]))->andWhere('se.arrdate >= :startDate AND se.arrdate < :endDate')
            ->andWhere($queryBuilder->expr()->andX(
                'geotagDep.lat IS NOT NULL AND geotagDep.lng IS NOT NULL',
                'geotagArr.lat IS NOT NULL AND geotagArr.lng IS NOT NULL'
            ))
            ->setParameters([
                ':userId' => $userId,
                ':startDate' => $startDate,
                ':endDate' => $endDate,
            ])->orderBy('se.arrdate');

        if ($agentId !== null) {
            $query->andWhere('tr.userAgent = :userAgentId')
                ->setParameter('userAgentId', $agentId);
        }

        return $query->getQuery();
    }

    private function getQueryPeriods(int $userId, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                'SUBSTRING(se.arrdate, 1, 4) AS year',
                'COUNT(se.tripsegmentid) AS count',
            ])->from(TripEntity::class, 'tr');

        $query = $queryBuilder
            ->leftJoin('tr.segments', 'se')
            ->innerJoin('se.depgeotagid', 'geotagDep')
            ->innerJoin('se.arrgeotagid', 'geotagArr')
            ->where('tr.user = :userId', 'tr.hidden = 0', 'tr.cancelled = 0', 'se.hidden = 0', 'se.arrdate < :today')
            ->andWhere($queryBuilder->expr()->in('tr.category', [
                TripEntity::CATEGORY_BUS,
                TripEntity::CATEGORY_TRAIN,
                TripEntity::CATEGORY_CRUISE,
                TripEntity::CATEGORY_FERRY,
                TripEntity::CATEGORY_TRANSFER,
            ]))->andWhere($queryBuilder->expr()->andX(
                'geotagDep.lat IS NOT NULL AND geotagDep.lng IS NOT NULL',
                'geotagArr.lat IS NOT NULL AND geotagArr.lng IS NOT NULL'
            ))
            ->setParameters([':userId' => $userId, ':today' => new \DateTime()])
            ->groupBy('year')
            ->orderBy('year', 'DESC');

        if ($agentId !== null) {
            $query->andWhere('tr.userAgent = :userAgentId')
                ->setParameter('userAgentId', $agentId);
        }

        return $query->getQuery();
    }
}
