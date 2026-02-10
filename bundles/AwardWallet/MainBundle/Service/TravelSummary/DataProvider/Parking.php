<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Parking as ParkingEntity;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation as ReservationModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * Class used to get parking lots.
 */
class Parking implements ReservationDataSourceInterface
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
        $parkingLots = $this->getQuery($userId, $startDate, $endDate, $agentId)
            ->getResult(Query::HYDRATE_ARRAY);
        $result = [];

        foreach ($parkingLots as $parking) {
            $model = new ReservationModel(
                $parking['id'],
                $parking['providerName'],
                $parking['startdatetime'],
                $parking['enddatetime'],
                (new Marker($parking['lat'], $parking['lng'], $parking['timeZoneLocation'], 'parking'))
                    ->setCity($parking['city'])
                    ->setCountry($parking['country'])
                    ->setStateCode($parking['stateCode'])
                    ->setCountryCode($parking['countryCode'])
                    ->setAddress($parking['location']),
                ParkingEntity::SEGMENT_MAP_START
            );

            $model->setDuration($this->intervalFormatter->formatDuration($parking['startdatetime'], $parking['enddatetime'], false, true));
            $model->setConfirmationNumber($parking['confirmationNumber']);

            $result[] = $model;
        }

        return $result;
    }

    private function getQuery(int $userId, \DateTime $startDate, \DateTime $endDate, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                'pa.id',
                'COALESCE(pa.parkingcompanyname, pr.shortname, \'\') AS providerName',
                'pa.confirmationNumber',
                'pa.location',
                'pa.startdatetime',
                'pa.enddatetime',
                'ge.lat',
                'ge.lng',
                'ge.city',
                'ge.country',
                'ge.stateCode',
                'ge.countryCode',
                'ge.timeZoneLocation',
            ])->from(ParkingEntity::class, 'pa');

        $query = $queryBuilder
            ->leftJoin('pa.provider', 'pr')
            ->innerJoin('pa.geotagid', 'ge')
            ->where('pa.user = :userId', 'pa.hidden = 0', 'pa.cancelled = 0')
            ->andWhere('pa.enddatetime >= :startDate AND pa.enddatetime < :endDate')
            ->andWhere($queryBuilder->expr()->andX('ge.lat IS NOT NULL', 'ge.lng IS NOT NULL'))
            ->setParameters([
                ':userId' => $userId,
                ':startDate' => $startDate,
                ':endDate' => $endDate,
            ])->orderBy('pa.enddatetime');

        if ($agentId !== null) {
            $query->andWhere('pa.userAgent = :userAgentId')
                ->setParameter('userAgentId', $agentId);
        }

        return $query->getQuery();
    }

    private function getQueryPeriods(int $userId, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                'SUBSTRING(pa.enddatetime, 1, 4) AS year',
                'COUNT(pa.id) AS count',
            ])->from(ParkingEntity::class, 'pa');

        $query = $queryBuilder
            ->innerJoin('pa.geotagid', 'ge')
            ->where('pa.user = :userId', 'pa.hidden = 0', 'pa.cancelled = 0', 'pa.enddatetime < :today')
            ->andWhere($queryBuilder->expr()->andX('ge.lat IS NOT NULL', 'ge.lng IS NOT NULL'))
            ->setParameters([':userId' => $userId, ':today' => new \DateTime()])
            ->groupBy('year')
            ->orderBy('year', 'DESC');

        if ($agentId !== null) {
            $query->andWhere('pa.userAgent = :userAgentId')
                ->setParameter('userAgentId', $agentId);
        }

        return $query->getQuery();
    }
}
