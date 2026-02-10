<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Entity\Room;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use Doctrine\ORM\EntityManager;

class ReservationSource extends AbstractSource
{
    public function __construct(EntityManager $entityManager)
    {
        $connection = $entityManager->getConnection();
        $selects = [];
        $joins = [];

        foreach ($this->getFields() as $field) {
            if (is_array($field) && sizeof($field) == 2) {
                $selects[] = implode(" AS ", $field);
            }
        }
        $sql = "
            SELECT
                r.ReservationID                                             AS RowID,
      		 	CONCAT('R.', r.ReservationID)                               AS SourceID,
      		 	ADDDATE(r.CheckOutDate, 30)                                 AS ExpirationDate,
                " . implode(",", $selects) . "
            FROM
                Reservation r
                " . implode(" ", $joins);
        $accSql = $sql . ' WHERE r.AccountID = :accountId';
        $itSql = $sql . ' WHERE r.ReservationID = :itineraryId';

        $this->query = $connection->prepare($accSql);
        $this->itineraryQuery = $connection->prepare($itSql);

        $this->update = $connection->prepare(
            "UPDATE Reservation SET ChangeDate = :changeDate WHERE ReservationID = :userData"
        );

        $this->repository = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class);
    }

    public function getProperties($accountId)
    {
        $this->query->execute(['accountId' => $accountId]);
        $rows = $this->query->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($rows as $row) {
            $row = $this->expandRoomArray($row);
            $result[$row['SourceID']] = new Properties(
                $this,
                $row['SourceID'],
                new \DateTime($row['ExpirationDate']),
                array_diff_key($row, ['RowID' => 0, 'SourceID' => 0, 'ExpirationDate' => 0, 'RoomArray' => 0]),
                $row['RowID']
            );
        }

        return $result;
    }

    public function getItineraryProperties($itineraryId)
    {
        [$code, $itineraryId] = explode('.', $itineraryId);

        if ($code !== 'R') {
            return [];
        }

        $this->itineraryQuery->execute(['itineraryId' => $itineraryId]);
        $rows = $this->itineraryQuery->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($rows as $row) {
            $row = $this->expandRoomArray($row);
            $result[$row['SourceID']] = new Properties(
                $this,
                $row['SourceID'],
                new \DateTime($row['ExpirationDate']),
                array_diff_key($row, ['RowID' => 0, 'SourceID' => 0, 'ExpirationDate' => 0, 'RoomArray' => 0]),
                $row['RowID']
            );
        }

        return $result;
    }

    private function expandRoomArray(array $row): array
    {
        $rooms = unserialize($row['RoomArray']);

        if (false === $rooms) {
            return $row;
        }
        $row[PropertiesList::ROOM_RATE] = implode(' | ', array_map(function (Room $room) {
            return $room->getRate();
        }, $rooms));
        $row[PropertiesList::ROOM_RATE_DESCRIPTION] = implode(' | ', array_map(function (Room $room) {
            return $room->getRateDescription();
        }, $rooms));
        $row[PropertiesList::ROOM_SHORT_DESCRIPTION] = implode(' | ', array_map(function (Room $room) {
            return $room->getShortDescription();
        }, $rooms));
        $row[PropertiesList::ROOM_LONG_DESCRIPTION] = implode(' | ', array_map(function (Room $room) {
            return $room->getLongDescription();
        }, $rooms));

        return $row;
    }

    private function getFields()
    {
        return [
            ['UNIX_TIMESTAMP(r.CheckInDate)', PropertiesList::CHECK_IN_DATE],
            ['UNIX_TIMESTAMP(r.CheckOutDate)', PropertiesList::CHECK_OUT_DATE],
            ['ParsedAccountNumbers', PropertiesList::ACCOUNT_NUMBERS],
            ['TravelAgencyParsedAccountNumbers', PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS],
            ['HotelName', PropertiesList::HOTEL_NAME],
            ['Address', PropertiesList::ADDRESS],
            ['Phone', PropertiesList::PHONE],
            ['Cost', PropertiesList::COST],
            ['Total', PropertiesList::TOTAL_CHARGE],
            ['CurrencyCode', PropertiesList::CURRENCY],
            ['UNIX_TIMESTAMP(ReservationDate)', PropertiesList::RESERVATION_DATE],
            ['CancellationPolicy', PropertiesList::CANCELLATION_POLICY],
            ['GuestCount', PropertiesList::GUEST_COUNT],
            ['TravelerNames', PropertiesList::TRAVELER_NAMES],
            ['KidsCount', PropertiesList::KIDS_COUNT],
            ['Total', PropertiesList::TOTAL_CHARGE],
            ['RoomCount', PropertiesList::ROOM_COUNT],
            ['Rooms', 'RoomArray'],
            ['Fax', PropertiesList::FAX],
            ['SpentAwards', PropertiesList::SPENT_AWARDS],
            ['EarnedAwards', PropertiesList::EARNED_AWARDS],
            ['TravelAgencyEarnedAwards', PropertiesList::TRAVEL_AGENCY_EARNED_AWARDS],
            ['FreeNights', PropertiesList::FREE_NIGHTS],
        ];
    }
}
