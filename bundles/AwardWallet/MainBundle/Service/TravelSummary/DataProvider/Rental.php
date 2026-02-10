<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Rental as RentalEntity;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Trip as TripModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * Class used to get car rental reservations.
 */
class Rental implements TripDataSourceInterface
{
    use ToolsTrait;

    private EntityManagerInterface $entityManager;
    private DateTimeIntervalFormatter $intervalFormatter;

    public function __construct(EntityManagerInterface $entityManager, DateTimeIntervalFormatter $intervalFormatter)
    {
        $this->entityManager = $entityManager;
        $this->intervalFormatter = $intervalFormatter;
    }

    public function getData(Owner $owner, \DateTime $startDate, \DateTime $endDate): array
    {
        [$userId, $agentId] = self::getUserIds($owner);
        $rentals = $this->getQuery($userId, $startDate, $endDate, $agentId)
            ->getResult(Query::HYDRATE_ARRAY);
        $result = [];

        foreach ($rentals as $rental) {
            $model = new TripModel(
                $rental['id'],
                $rental['providerName'],
                $rental['pickupdatetime'],
                $rental['dropoffdatetime'],
                (new Marker($rental['depLat'], $rental['depLng'], $rental['depTimezone'], 'rental'))
                    ->setCity($rental['depCityName'])
                    ->setCountry($rental['depCountryName'])
                    ->setStateCode($rental['depStateCode'])
                    ->setCountryCode($rental['depCountryCode'])
                    ->setLocationName($rental['pickuplocation']),
                (new Marker($rental['arrLat'], $rental['arrLng'], $rental['arrTimezone'], 'rental'))
                    ->setCity($rental['arrCityName'])
                    ->setCountry($rental['arrCountryName'])
                    ->setStateCode($rental['arrStateCode'])
                    ->setCountryCode($rental['arrCountryCode'])
                    ->setLocationName($rental['dropofflocation']),
                RentalEntity::SEGMENT_MAP_START
            );

            $model->setDuration($this->intervalFormatter->formatDuration($rental['pickupdatetime'], $rental['dropoffdatetime'], false, true));

            $result[] = $model;
        }

        return $result;
    }

    private function getQuery(int $userId, \DateTime $startDate, \DateTime $endDate, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                're.id',
                'COALESCE(re.rentalCompanyName, pr.shortname, \'\') AS providerName',
                're.pickuplocation',
                're.pickupdatetime',
                'geotagDep.city AS depCityName',
                'geotagDep.country AS depCountryName',
                'geotagDep.stateCode AS depStateCode',
                'geotagDep.countryCode AS depCountryCode',
                'geotagDep.lat AS depLat',
                'geotagDep.lng AS depLng',
                'geotagDep.timeZoneLocation AS depTimezone',
                're.dropofflocation',
                're.dropoffdatetime',
                'geotagArr.city AS arrCityName',
                'geotagArr.country AS arrCountryName',
                'geotagArr.stateCode AS arrStateCode',
                'geotagArr.countryCode AS arrCountryCode',
                'geotagArr.lat AS arrLat',
                'geotagArr.lng AS arrLng',
                'geotagArr.timeZoneLocation AS arrTimezone',
            ])
            ->from(RentalEntity::class, 're');

        $query = $queryBuilder
            ->leftJoin('re.provider', 'pr')
            ->innerJoin('re.pickupgeotagid', 'geotagDep')
            ->innerJoin('re.dropoffgeotagid', 'geotagArr')
            ->where('re.user = :userId', 're.hidden = 0', 're.cancelled = 0')
            ->andWhere('re.dropoffdatetime >= :startDate AND re.dropoffdatetime < :endDate')
            ->andWhere($queryBuilder->expr()->andX(
                'geotagDep.lat IS NOT NULL AND geotagDep.lng IS NOT NULL',
                'geotagArr.lat IS NOT NULL AND geotagArr.lng IS NOT NULL'
            ))
            ->setParameters([
                ':userId' => $userId,
                ':startDate' => $startDate,
                ':endDate' => $endDate,
            ])->orderBy('re.dropoffdatetime');

        if ($agentId !== null) {
            $query->andWhere('re.userAgent = :userAgentId')
                ->setParameter('userAgentId', $agentId);
        }

        return $query->getQuery();
    }

    private function getQueryPeriods(int $userId, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                'SUBSTRING(re.dropoffdatetime, 1, 4) AS year',
                'COUNT(re.id) AS count',
            ])->from(RentalEntity::class, 're');

        $query = $queryBuilder
            ->innerJoin('re.pickupgeotagid', 'geotagDep')
            ->innerJoin('re.dropoffgeotagid', 'geotagArr')
            ->where('re.user = :userId', 're.hidden = 0', 're.cancelled = 0', 're.dropoffdatetime < :today')
            ->andWhere($queryBuilder->expr()->andX(
                'geotagDep.lat IS NOT NULL AND geotagDep.lng IS NOT NULL',
                'geotagArr.lat IS NOT NULL AND geotagArr.lng IS NOT NULL'
            ))
            ->setParameters([':userId' => $userId, ':today' => new \DateTime()])
            ->groupBy('year')
            ->orderBy('year', 'DESC');

        if ($agentId !== null) {
            $query->andWhere('re.userAgent = :userAgentId')
                ->setParameter('userAgentId', $agentId);
        }

        return $query->getQuery();
    }
}
