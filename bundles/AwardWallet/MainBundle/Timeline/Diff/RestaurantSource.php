<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use Doctrine\ORM\EntityManager;

class RestaurantSource extends AbstractSource
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
                e.RestaurantID                                        AS RowID,
                CONCAT('E.', e.RestaurantID)                          AS SourceID,
                ADDDATE(e.EndDate, 30)                                AS ExpirationDate,
                " . implode(",", $selects) . "
            FROM
                Restaurant e
                " . implode(" ", $joins);
        $accSql = $sql . ' WHERE e.AccountID = :accountId';
        $itSql = $sql . ' WHERE e.RestaurantID = :itineraryId';

        $this->query = $connection->prepare($accSql);
        $this->itineraryQuery = $connection->prepare($itSql);

        $this->update = $connection->prepare(
            "UPDATE Restaurant SET ChangeDate = :changeDate WHERE RestaurantID = :userData"
        );

        $this->repository = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Restaurant::class);
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

        if ($code !== 'E') {
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
            ['UNIX_TIMESTAMP(e.StartDate)', PropertiesList::START_DATE],
            ['UNIX_TIMESTAMP(e.EndDate)', PropertiesList::END_DATE],
            ['e.Name', PropertiesList::EVENT_NAME],
            ['e.Address', PropertiesList::ADDRESS],
            ['e.Phone', PropertiesList::PHONE],
            ['TravelerNames', PropertiesList::TRAVELER_NAMES],
            ['GuestCount', PropertiesList::GUEST_COUNT],
            ['Total', PropertiesList::TOTAL_CHARGE],
            ['CurrencyCode', PropertiesList::CURRENCY],
            ['SpentAwards', PropertiesList::SPENT_AWARDS],
            ['EarnedAwards', PropertiesList::EARNED_AWARDS],
            ['ParsedAccountNumbers', PropertiesList::ACCOUNT_NUMBERS],
        ];
    }
}
