<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use Doctrine\ORM\EntityManager;

class TripSource extends AbstractSource
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
                ts.TripSegmentID                                            AS RowID,
                CONCAT('S.', ts.TripSegmentID)                              AS SourceID,
                ADDDATE(ts.ArrDate, 30)                                     AS ExpirationDate,
                " . implode(",", $selects) . "
            FROM
                TripSegment ts
                JOIN Trip t ON ts.TripID = t.TripID
                " . implode(" ", $joins);
        $accSql = $sql . ' WHERE t.AccountID = :accountId';
        $itSql = $sql . " WHERE ts.TripID = :itineraryId";

        $this->query = $connection->prepare($accSql);
        $this->itineraryQuery = $connection->prepare($itSql);

        $this->update = $connection->prepare("
			UPDATE TripSegment set ChangeDate = :changeDate where TripSegmentID = :userData
		");

        $this->repository = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);
    }

    /**
     * TODO: move to entity.
     */
    public static function getSourceId(Tripsegment $segment)
    {
        return "S." . $segment->getTripsegmentid();
    }

    public function getProperties($accountId)
    {
        $this->query->execute(['accountId' => $accountId]);
        $rows = $this->query->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($rows as $row) {
            $row['Seats'] = $this->filterCommaProperty($row['Seats']);
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

        if ($code !== 'T') {
            return [];
        }

        $this->itineraryQuery->execute(['itineraryId' => $itineraryId]);
        $rows = $this->itineraryQuery->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($rows as $row) {
            $row['Seats'] = $this->filterCommaProperty($row['Seats']);
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

    /**
     * filter comma separated property like '3, 1, 2, --' to ordered list without empty values, like '1, 2, 3'.
     *
     * @param string $property
     * @return string
     */
    private function filterCommaProperty($property)
    {
        $result = [];

        foreach (explode(", ", $property) as $value) {
            $value = Properties::trimValue($value);

            if (!empty($value)) {
                $result[] = $value;
            }
        }
        sort($result);

        return implode(", ", $result);
    }

    private function getFields()
    {
        return [
            ['UNIX_TIMESTAMP(ts.DepDate)', PropertiesList::DEPARTURE_DATE],
            ['UNIX_TIMESTAMP(ts.ArrDate)', PropertiesList::ARRIVAL_DATE],
            ['ts.FlightNumber', PropertiesList::FLIGHT_NUMBER],
            ['ts.AirlineName', PropertiesList::AIRLINE_NAME],
            ['ts.Seats', PropertiesList::SEATS],
            ['ts.BaggageClaim', PropertiesList::BAGGAGE_CLAIM],
            ['ts.DepartureTerminal', PropertiesList::DEPARTURE_TERMINAL],
            ['ts.ArrivalTerminal', PropertiesList::ARRIVAL_TERMINAL],
            ['ts.DepartureGate', PropertiesList::DEPARTURE_GATE],
            ['ts.ArrivalGate', PropertiesList::ARRIVAL_GATE],
            ['t.TravelerNames', PropertiesList::TRAVELER_NAMES],
            ['t.ParsedAccountNumbers', PropertiesList::ACCOUNT_NUMBERS],
            ['t.Total', PropertiesList::TOTAL_CHARGE],
            ['t.Cost', PropertiesList::COST],
            ['t.CurrencyCode', PropertiesList::CURRENCY],
            ['t.SpentAwards', PropertiesList::SPENT_AWARDS],
            ['t.EarnedAwards', PropertiesList::EARNED_AWARDS],
            ['ts.Aircraft', PropertiesList::AIRCRAFT],
            ['ts.TraveledMiles', PropertiesList::TRAVELED_MILES],
            ['ts.CabinClass', PropertiesList::FLIGHT_CABIN_CLASS],
            ['ts.BookingClass', PropertiesList::BOOKING_CLASS],
            ['ts.Meal', PropertiesList::MEAL],
            ['ts.Smoking', PropertiesList::IS_SMOKING],
            ['ts.Stops', PropertiesList::STOPS_COUNT],

            /** Cruises  **/
            ['t.ShipName', PropertiesList::SHIP_NAME],
            ['t.ShipCode', PropertiesList::SHIP_CODE],
            ['t.CruiseName', PropertiesList::CRUISE_NAME],
            ['t.Deck', PropertiesList::DECK],
            ['t.CabinNumber', PropertiesList::SHIP_CABIN_NUMBER],
            ['t.CabinClass', PropertiesList::SHIP_CABIN_CLASS],
        ];
    }
}
