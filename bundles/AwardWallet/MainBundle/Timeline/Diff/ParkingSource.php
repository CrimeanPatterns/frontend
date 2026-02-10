<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use Doctrine\ORM\EntityManager;

class ParkingSource extends AbstractSource
{
    public function __construct(EntityManager $entityManager)
    {
        $connection = $entityManager->getConnection();
        $selects = [];
        $joins = [];

        foreach ($this->getFields() as $field) {
            $selects[] = implode(" AS ", $field);
        }
        $sql = "
            SELECT
                p.ParkingID AS RowID,
                CONCAT('P.', p.ParkingID) AS SourceID,
                ADDDATE(p.EndDatetime, 30) AS ExpirationDate,
                " . implode(",", $selects) . "
            FROM
                Parking p
                " . implode(" ", $joins);
        $accSql = $sql . ' WHERE p.AccountID = :accountId';
        $itSql = $sql . ' WHERE p.ParkingID = :itineraryId';

        $this->query = $connection->prepare($accSql);
        $this->itineraryQuery = $connection->prepare($itSql);

        $this->update = $connection->prepare(
            "UPDATE Parking SET ChangeDate = :changeDate WHERE ParkingID = :userData"
        );

        $this->repository = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Parking::class);
    }

    /**
     * should return associative array. keys are sourceId fields.
     *
     * @param int $accountId
     * @return Properties[]
     */
    public function getProperties($accountId)
    {
        $this->query->execute(['accountId' => $accountId]);
        $rows = $this->query->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($rows as $row) {
            $result[$row['SourceID']] = new Properties(
                $this,
                $row['SourceID'],
                new \DateTime($row['ExpirationDate']),
                array_diff_key($row, ['RowID' => 0, 'SourceID' => 0, 'ExpirationDate' => 0]),
                $row['RowID']
            );
        }

        return $result;
    }

    public function getItineraryProperties($itineraryId)
    {
        [$code, $itineraryId] = explode('.', $itineraryId);

        if ($code !== 'P') {
            return [];
        }

        $this->itineraryQuery->execute(['itineraryId' => $itineraryId]);
        $rows = $this->itineraryQuery->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($rows as $row) {
            $result[$row['SourceID']] = new Properties(
                $this,
                $row['SourceID'],
                new \DateTime($row['ExpirationDate']),
                array_diff_key($row, ['RowID' => 0, 'SourceID' => 0, 'ExpirationDate' => 0]),
                $row['RowID']
            );
        }

        return $result;
    }

    private function getFields()
    {
        return [
            ['UNIX_TIMESTAMP(StartDatetime)', PropertiesList::START_DATE],
            ['Location', PropertiesList::LOCATION],
            ['Phone', PropertiesList::PHONE],
            ['UNIX_TIMESTAMP(EndDatetime)', PropertiesList::DROP_OFF_DATE],
            ['Plate', PropertiesList::LICENSE_PLATE],
            ['Spot', PropertiesList::SPOT_NUMBER],
            ['ParkingCompanyName', PropertiesList::PARKING_COMPANY],
            ['CarDescription', PropertiesList::CAR_DESCRIPTION],
            ['Total', PropertiesList::TOTAL_CHARGE],
            ['Discount', PropertiesList::DISCOUNT],
            ['CurrencyCode', PropertiesList::CURRENCY],
            ['SpentAwards', PropertiesList::SPENT_AWARDS],
            ['EarnedAwards', PropertiesList::EARNED_AWARDS],
            ['TravelerNames', PropertiesList::TRAVELER_NAMES],
        ];
    }
}
