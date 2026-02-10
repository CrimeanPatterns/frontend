<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use Doctrine\ORM\EntityManager;

class RentalSource extends AbstractSource
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
                l.RentalID                                                AS RowID,
                CONCAT('L.', l.RentalID)                                  AS SourceID,
                ADDDATE(l.DropoffDatetime, 30)                            AS ExpirationDate,
                " . implode(",", $selects) . "
            FROM
                Rental l
                " . implode(" ", $joins);
        $accSql = $sql . ' WHERE l.AccountID = :accountId';
        $itSql = $sql . ' WHERE l.RentalID = :itineraryId';

        $this->query = $connection->prepare($accSql);
        $this->itineraryQuery = $connection->prepare($itSql);

        $this->update = $connection->prepare(
            "UPDATE Rental SET ChangeDate = :changeDate WHERE RentalID = :userData"
        );

        $this->repository = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Rental::class);
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

        if ($code !== 'L') {
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
            ['UNIX_TIMESTAMP(l.PickupDatetime)', PropertiesList::PICK_UP_DATE],
            ['PickupLocation', PropertiesList::PICK_UP_LOCATION],
            ['PickupHours', PropertiesList::PICK_UP_HOURS],
            ['PickupPhone', PropertiesList::PICK_UP_PHONE],
            ['PickUpFax', PropertiesList::PICK_UP_FAX],
            ['UNIX_TIMESTAMP(l.DropoffDatetime)', PropertiesList::DROP_OFF_DATE],
            ['DropoffLocation', PropertiesList::DROP_OFF_LOCATION],
            ['DropoffHours', PropertiesList::DROP_OFF_HOURS],
            ['DropoffPhone', PropertiesList::DROP_OFF_PHONE],
            ['DropOffFax', PropertiesList::DROP_OFF_FAX],
            ['RentalCompanyName', PropertiesList::RENTAL_COMPANY],
            ['CarModel', PropertiesList::CAR_MODEL],
            ['CarType', PropertiesList::CAR_TYPE],
            ['CarImageUrl', PropertiesList::CAR_IMAGE_URL],
            ['Total', PropertiesList::TOTAL_CHARGE],
            ['Discount', PropertiesList::DISCOUNT],
            ['CurrencyCode', PropertiesList::CURRENCY],
            ['SpentAwards', PropertiesList::SPENT_AWARDS],
            ['EarnedAwards', PropertiesList::EARNED_AWARDS],
            ['TravelerNames', PropertiesList::TRAVELER_NAMES],
        ];
    }
}
