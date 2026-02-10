<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Reservation as ReservationEntity;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Marker;
use AwardWallet\MainBundle\Service\TravelSummary\Data\Reservation as ReservationModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

/**
 * Class used to get hotel reservations.
 */
class Hotel implements ReservationDataSourceInterface
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
        $hotels = $this->getQuery($userId, $startDate, $endDate, $agentId)
            ->getResult(Query::HYDRATE_ARRAY);
        $result = [];

        foreach ($hotels as $hotel) {
            $model = new ReservationModel(
                $hotel['id'],
                $hotel['hotelname'],
                $hotel['checkindate'],
                $hotel['checkoutdate'],
                (new Marker($hotel['lat'], $hotel['lng'], $hotel['timeZoneLocation'], 'hotel'))
                    ->setCity($hotel['city'])
                    ->setCountry($hotel['country'])
                    ->setStateCode($hotel['stateCode'])
                    ->setCountryCode($hotel['countryCode'])
                    ->setAddress($hotel['address']),
                ReservationEntity::SEGMENT_MAP_START
            );

            $model->setDuration($this->intervalFormatter->getNightCount($hotel['checkindate'], $hotel['checkoutdate']));
            $model->setConfirmationNumber($hotel['confirmationNumber']);

            $result[] = $model;
        }

        return $result;
    }

    private function getQuery(int $userId, \DateTime $startDate, \DateTime $endDate, ?int $agentId): Query
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                're.id',
                're.hotelname',
                're.address',
                're.checkindate',
                're.checkoutdate',
                're.confirmationNumber',
                'ge.lat',
                'ge.lng',
                'ge.city',
                'ge.country',
                'ge.stateCode',
                'ge.countryCode',
                'ge.timeZoneLocation',
            ])->from(ReservationEntity::class, 're');

        $query = $queryBuilder
            ->innerJoin('re.geotagid', 'ge')
            ->where('re.user = :userId', 're.hidden = 0', 're.cancelled = 0')
            ->andWhere('re.checkoutdate >= :startDate AND re.checkoutdate < :endDate')
            ->andWhere($queryBuilder->expr()->andX('ge.lat IS NOT NULL', 'ge.lng IS NOT NULL'))
            ->setParameters([
                ':userId' => $userId,
                ':startDate' => $startDate,
                ':endDate' => $endDate,
            ])->orderBy('re.checkoutdate');

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
                'SUBSTRING(re.checkoutdate, 1, 4) AS year',
                'COUNT(re.id) AS count',
            ])->from(ReservationEntity::class, 're');

        $query = $queryBuilder
            ->innerJoin('re.geotagid', 'ge')
            ->where('re.user = :userId', 're.hidden = 0', 're.cancelled = 0', 're.checkoutdate < :today')
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
