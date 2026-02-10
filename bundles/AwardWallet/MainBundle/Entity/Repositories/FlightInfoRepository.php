<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * FlightInfoRepository.
 */
class FlightInfoRepository extends EntityRepository
{
    public function findOrCreate($airline, $flightNumber, \DateTime $flightDate, $depCode, $arrCode)
    {
        $connection = $this->getEntityManager()->getConnection();

        $flightInfo = $this->findOneBy([
            'Airline' => $airline,
            'FlightNumber' => $flightNumber,
            'FlightDate' => $flightDate,
            'DepCode' => $depCode,
            'ArrCode' => $arrCode,
        ]);

        if (empty($flightInfo)) {
            // don't use Doctrine!
            // ignore insert conflicts
            $stmt = $connection->prepare("
                INSERT IGNORE INTO FlightInfo(Airline, FlightNumber, FlightDate, DepCode, ArrCode, CreateDate, UpdatesCount, Schedule)
                VALUES (:airline, :flightNumber, :flightDate, :depCode, :arrCode, NOW(), 0, '')
            ");
            $stmt->bindValue(':airline', $airline);
            $stmt->bindValue(':flightNumber', $flightNumber);
            $stmt->bindValue(':flightDate', $flightDate->format('Y-m-d'));
            $stmt->bindValue(':depCode', $depCode);
            $stmt->bindValue(':arrCode', $arrCode);
            $stmt->execute();

            $flightInfo = $this->findOneBy([
                'Airline' => $airline,
                'FlightNumber' => $flightNumber,
                'FlightDate' => $flightDate,
                'DepCode' => $depCode,
                'ArrCode' => $arrCode,
            ]);

            if (empty($flightInfo)) {
                throw new \Exception('FlightInfo record not exists and not inserted');
            }
        }

        return $flightInfo;
    }
}
