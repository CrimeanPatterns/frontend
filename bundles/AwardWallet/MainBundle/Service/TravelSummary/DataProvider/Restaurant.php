<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Restaurant as RestaurantEntity;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation as ReservationModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * Class used to get restaurants and all types of "Event":
 * meeting, show, event, conference, rave.
 */
class Restaurant implements ReservationDataSourceInterface
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
        $restaurants = $this->getQuery($userId, $startDate, $endDate, $agentId)
            ->getResult(Query::HYDRATE_ARRAY);
        $result = [];

        foreach ($restaurants as $restaurant) {
            $category = RestaurantEntity::EVENT_TYPE_NAMES[$restaurant['eventtype']];
            $model = new ReservationModel(
                $restaurant['id'],
                $restaurant['name'],
                $restaurant['startdate'],
                $restaurant['enddate'],
                (new Marker($restaurant['lat'], $restaurant['lng'], $restaurant['timeZoneLocation'], strtolower($category)))
                    ->setCity($restaurant['city'])
                    ->setCountry($restaurant['country'])
                    ->setStateCode($restaurant['stateCode'])
                    ->setCountryCode($restaurant['countryCode'])
                    ->setAddress($restaurant['address']),
                RestaurantEntity::SEGMENT_MAP
            );

            if ($restaurant['enddate'] !== null) {
                $model->setDuration($this->intervalFormatter->formatDuration($restaurant['startdate'], $restaurant['enddate'], false, true));
            }

            $model->setConfirmationNumber($restaurant['confirmationNumber']);

            $result[] = $model;
        }

        return $result;
    }

    private function getQuery(int $userId, \DateTime $startDate, \DateTime $endDate, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                're.id',
                're.confirmationNumber',
                're.name',
                're.address',
                're.eventtype',
                're.startdate',
                're.enddate',
                'ge.lat',
                'ge.lng',
                'ge.city',
                'ge.country',
                'ge.stateCode',
                'ge.countryCode',
                'ge.timeZoneLocation',
            ])->from(RestaurantEntity::class, 're');

        $query = $queryBuilder
            ->innerJoin('re.geotagid', 'ge')
            ->where('re.user = :userId', 're.hidden = 0', 're.cancelled = 0')
            ->andWhere($queryBuilder->expr()->in('re.eventtype', RestaurantEntity::EVENT_TYPES))
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX('re.startdate >= :startDate', 're.enddate < :endDate'),
                $queryBuilder->expr()->andX('re.startdate >= :startDate', 're.enddate IS NULL', 're.startdate < :endDate')
            ))
            ->andWhere($queryBuilder->expr()->andX('ge.lat IS NOT NULL', 'ge.lng IS NOT NULL'))
            ->setParameters([
                ':userId' => $userId,
                ':startDate' => $startDate,
                ':endDate' => $endDate,
            ])->orderBy('re.enddate');

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
                'SUBSTRING(re.startdate, 1, 4) AS year',
                'COUNT(re.id) AS count',
            ])->from(RestaurantEntity::class, 're');

        $query = $queryBuilder
            ->innerJoin('re.geotagid', 'ge')
            ->where('re.user = :userId', 're.hidden = 0', 're.cancelled = 0', 're.enddate < :today')
            ->andWhere($queryBuilder->expr()->in('re.eventtype', RestaurantEntity::EVENT_TYPES))
            ->andWhere($queryBuilder->expr()->andX('ge.lat IS NOT NULL', 'ge.lng IS NOT NULL'))
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
