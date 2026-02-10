<?php

namespace AwardWallet\MainBundle\Entity\Query;

use AwardWallet\Common\Entity\Aircode;
use Doctrine\ORM\EntityManagerInterface;

class AirportQuery
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return Aircode[]
     */
    public function findAircodeByQuery(string $query, int $limit = 10): array
    {
        $foundByCodes = $this->em->createQueryBuilder()
            ->select('aircode')
            ->from(Aircode::class, 'aircode')
            ->orWhere('aircode.aircode = :query')
            ->orWhere('aircode.icaoCode = :query')
            ->setParameter(':query', $query)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        $foundByLike = $this->em->createQueryBuilder()
            ->select('aircode')
            ->from(Aircode::class, 'aircode')
            ->orWhere('aircode.aircode LIKE :query')
            ->orWhere('aircode.icaoCode LIKE :query')
            ->orWhere('aircode.airname LIKE :query')
            ->orWhere('aircode.cityname LIKE :query')
            ->setParameter(':query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        $mergedAirports = $foundByCodes;

        foreach ($foundByLike as $airport) {
            if (!in_array($airport, $mergedAirports)) {
                $mergedAirports[] = $airport;
            }
        }
        array_slice($mergedAirports, 0, $limit);

        return $mergedAirports;
    }

    /**
     * Update popularity of all or one airports.
     *
     * @return int The number of affected rows
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateAircodePopularity(?string $airCode = null): int
    {
        $timeInterval = 'INTERVAL 6 MONTH';
        $connection = $this->em->getConnection();

        if (!empty($airCode)) {
            $result = $connection->executeStatement('
                UPDATE
                  AirCode ac
                SET Popularity = IFNULL(
                  ( SELECT COUNT(DepCode)
                    FROM TripSegment ts
                    WHERE ts.DepCode = ac.AirCode AND DepDate > NOW() AND DepDate < DATE_ADD(NOW(), ' . $timeInterval . ') AND Hidden = 0
                  ), 0)
                WHERE ac.AirCode = ?;
            ', [$airCode], [\PDO::PARAM_STR]);
        } else {
            $result = $connection->executeStatement('
                UPDATE
                  AirCode ac
                  LEFT JOIN (
                    SELECT DepCode, COUNT(DepCode) AS Count
                    FROM TripSegment 
                    WHERE DepDate > NOW() AND DepDate < DATE_ADD(NOW(), ' . $timeInterval . ') AND Hidden = 0
                    GROUP BY DepCode
                  ) ts ON ts.DepCode = ac.AirCode
                SET Popularity = IFNULL(ts.Count, 0);
            ');
        }

        return $result;
    }
}
