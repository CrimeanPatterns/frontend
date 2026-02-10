<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * Class used to get flights.
 */
class Flight implements TripDataSourceInterface
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
        $flights = $this->getQuery($userId, $startDate, $endDate, $agentId)
            ->getResult(Query::HYDRATE_ARRAY);
        $result = [];

        foreach ($flights as $flight) {
            $model = new TripModel(
                $flight['tripsegmentid'],
                $flight['airlineName'],
                $flight['depdate'],
                $flight['arrdate'],
                (new Marker($flight['depLat'], $flight['depLng'], $flight['depTimezone'], 'air'))
                    ->setCity($flight['depCityName'])
                    ->setAirCode($flight['depcode']),
                (new Marker($flight['arrLat'], $flight['arrLng'], $flight['arrTimezone'], 'air'))
                    ->setCountry($flight['arrCountryName'])
                    ->setCountryCode($flight['arrCountryCode'])
                    ->setAirCode($flight['arrcode']),
                Trip::SEGMENT_MAP
            );
            $model->setAirlineCode($flight['airlineCode']);

            $result[] = $model;
        }

        return $result;
    }

    private function getQuery(int $userId, \DateTime $startDate, \DateTime $endDate, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                'se.tripsegmentid',
                'se.depcode',
                'se.depdate',
                'COALESCE(NULLIF(aircodeDep.cityname, \'\'), geotagDep.city) AS depCityName',
                'COALESCE(aircodeDep.lat, geotagDep.lat) AS depLat',
                'COALESCE(aircodeDep.lng, geotagDep.lng) AS depLng',
                'COALESCE(aircodeDep.timeZoneLocation, geotagDep.timeZoneLocation) AS depTimezone',
                'se.arrcode',
                'se.arrdate',
                'COALESCE(NULLIF(aircodeArr.countryname, \'\'), geotagArr.country) AS arrCountryName',
                'COALESCE(NULLIF(aircodeArr.countrycode, \'\'), geotagArr.countryCode) AS arrCountryCode',
                'COALESCE(aircodeArr.lat, geotagArr.lat) AS arrLat',
                'COALESCE(aircodeArr.lng, geotagArr.lng) AS arrLng',
                'COALESCE(aircodeArr.timeZoneLocation, geotagArr.timeZoneLocation) AS arrTimezone',
                'COALESCE(airline.name, se.airlineName, \'\') AS airlineName',
                'airline.code AS airlineCode',
            ])->from(Trip::class, 'tr');

        $query = $queryBuilder
            ->leftJoin('tr.segments', 'se')
            ->leftJoin('se.airline', 'airline')
            ->innerJoin(Aircode::class, 'aircodeDep', 'WITH', 'se.depcode = aircodeDep.aircode')
            ->innerJoin(Aircode::class, 'aircodeArr', 'WITH', 'se.arrcode = aircodeArr.aircode')
            ->innerJoin('se.depgeotagid', 'geotagDep')
            ->innerJoin('se.arrgeotagid', 'geotagArr')
            ->where('tr.user = :userId', 'tr.hidden = 0', 'tr.cancelled = 0', 'tr.category = :category', 'se.hidden = 0')
            ->andWhere($queryBuilder->expr()->andX(
                'geotagDep.lat IS NOT NULL AND geotagDep.lng IS NOT NULL',
                'geotagArr.lat IS NOT NULL AND geotagArr.lng IS NOT NULL'
            ))
            ->andWhere('se.arrdate >= :startDate AND se.arrdate < :endDate')
            ->setParameters([
                ':userId' => $userId,
                ':category' => Trip::CATEGORY_AIR,
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
            ])->from(Trip::class, 'tr');

        $query = $queryBuilder
            ->leftJoin('tr.segments', 'se')
            ->innerJoin('se.depgeotagid', 'geotagDep')
            ->innerJoin('se.arrgeotagid', 'geotagArr')
            ->where('tr.user = :userId', 'tr.hidden = 0', 'tr.cancelled = 0', 'tr.category = :category', 'se.hidden = 0', 'se.arrdate < :today')
            ->andWhere($queryBuilder->expr()->andX(
                'geotagDep.lat IS NOT NULL AND geotagDep.lng IS NOT NULL',
                'geotagArr.lat IS NOT NULL AND geotagArr.lng IS NOT NULL'
            ))
            ->setParameters([':userId' => $userId, ':category' => Trip::CATEGORY_AIR, ':today' => new \DateTime()])
            ->groupBy('year')
            ->orderBy('year', 'DESC');

        if ($agentId !== null) {
            $query->andWhere('tr.userAgent = :userAgentId')
                ->setParameter('userAgentId', $agentId);
        }

        return $query->getQuery();
    }
}
