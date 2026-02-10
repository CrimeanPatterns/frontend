<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Entity\Room;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MergeExtPropertiesCommand extends Command
{
    public const BATCH_SIZE = 1000;

    public const TABLE_TO_LETTER = [
        'TripSegment' => 'S',
        'Trip' => 'T',
        'Reservation' => 'R',
        'Rental' => 'L',
        'Restaurant' => 'E',
    ];

    public const MIGRATE_UPDATES_SINCE = '2000-01-01';
    public const MIGRATION_DATE = '2020-05-22';

    public static $defaultName = 'aw:merge-ext-properties';
    /**
     * @var Connection
     */
    private $archiveConnection;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Connection $archiveConnection)
    {
        parent::__construct();
        $this->archiveConnection = $archiveConnection;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        /*$this->write("updating reservations");
        $this->archiveConnection->executeUpdate("UPDATE Reservation SET Rooms = 'a:0:{}' WHERE Rooms IS NULL AND UpdateDate >= '" . self::MIGRATE_UPDATES_SINCE . "'");
        $this->write("updating rentals");
        $this->archiveConnection->executeUpdate("UPDATE Rental SET RentalCompanyName = ProviderName WHERE UpdateDate >= '" . self::MIGRATE_UPDATES_SINCE . "'");
        foreach (['TripSegment', 'Trip', 'Reservation', 'Rental', 'Restaurant'] as $table) {
            $this->archiveConnection->beginTransaction();
            $this->updateTable($table);
            $this->archiveConnection->commit();
        }
        */

        // TODO maybe save as a text field too?
        $this->archiveConnection->executeUpdate("
            UPDATE TripSegment 
            JOIN ExtProperty ON ExtProperty.SourceTable = 'S' AND ExtProperty.SourceID = TripSegmentID AND ExtProperty.Name = 'Aircraft' 
            LEFT JOIN Aircraft AircraftByIata ON ExtProperty.Value = AircraftByIata.IataCode COLLATE utf8_general_ci 
            LEFT JOIN Aircraft AircraftByName ON ExtProperty.Value = AircraftByName.Name COLLATE utf8_general_ci 
            LEFT JOIN Aircraft AircraftByShortName ON ExtProperty.Value = AircraftByShortName.ShortName COLLATE utf8_general_ci 
            SET TripSegment.AircraftID = COALESCE(AircraftByIata.AircraftID, AircraftByName.AircraftID, AircraftByShortName.AircraftID)
            WHERE ExtProperty.ExtPropertyID IS NOT NULL AND ExtPropertyMerged = FALSE
        ");
        $migrateSince = self::MIGRATE_UPDATES_SINCE;
        $this->archiveConnection->executeUpdate("
            UPDATE TripSegment
              JOIN Trip USING (TripID)
            SET TripSegment.MarketingAirlineConfirmationNumber = Trip.RecordLocator
            WHERE Trip.Category = 1 AND Trip.RecordLocator IS NOT NULL AND (Trip.UpdateDate >= $migrateSince OR TripSegment.ChangeDate >= $migrateSince OR Trip.CreateDate >= $migrateSince)
         ");

        return 0;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema)
    {
        foreach (['TripSegment', 'Trip', 'Reservation', 'Rental', 'Restaurant'] as $table) {
            $this->reverseUpdateTable($table);
        }
        $this->archiveConnection->executeUpdate('UPDATE Rental SET ProviderName = RentalCompanyName');
        $this->archiveConnection->executeUpdate("UPDATE Reservation SET Rooms = NULL WHERE Rooms = 'a:0:{}'");
    }

    private function write(string $message)
    {
        $this->output->writeln($message);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateTable(string $table)
    {
        $updateSince = self::MIGRATE_UPDATES_SINCE;

        if ('TripSegment' === $table) {
            $this->archiveConnection->executeUpdate("UPDATE TripSegment JOIN Trip USING (TripID) SET TripSegment.ExtPropertyMerged = FALSE WHERE TripSegment.ChangeDate >= '$updateSince' OR Trip.UpdateDate >= '$updateSince'");
        } else {
            $this->archiveConnection->executeUpdate("UPDATE $table SET ExtPropertyMerged = FALSE WHERE UpdateDate >= '$updateSince'");
        }
        $count = $this->archiveConnection->executeQuery("SELECT count(*) AS cnt FROM $table WHERE ExtPropertyMerged = FALSE")->fetchColumn(0);
        $this->write("Processing $table, $count rows:");
        $builder = $this->buildQuery($table);
        $processedCounter = 0;
        $updatedCounter = 0;

        do {
            $timestamp = time();
            $builder->setMaxResults(self::BATCH_SIZE);
            $statement = $builder->execute();

            while ($nextRow = $statement->fetch(\PDO::FETCH_ASSOC)) {
                switch ($table) {
                    case 'TripSegment':
                        $updatedCounter += $this->processTripSegment($nextRow);

                        break;

                    case 'Trip':
                        $updatedCounter += $this->processTrip($nextRow);

                        break;

                    case 'Reservation':
                        $updatedCounter += $this->processReservation($nextRow);

                        break;

                    case 'Rental':
                        $updatedCounter += $this->processRental($nextRow);

                        break;

                    case 'Restaurant':
                        $updatedCounter += $this->processRestaurant($nextRow);

                        break;

                    default:
                        throw new \LogicException("Invalid table");
                }
                $processedCounter++;

                if (0 === $processedCounter % self::BATCH_SIZE) {
                    $timer = time() - $timestamp;
                    $this->write("{$updatedCounter}/{$processedCounter}/{$count} ({$timer}s)");
                }
            }
            $this->archiveConnection->commit();
            $this->archiveConnection->beginTransaction();
        } while ($statement->rowCount());
        $this->write("{$updatedCounter}/{$processedCounter}/{$count}");
    }

    private function buildQuery(string $table): QueryBuilder
    {
        $itineraryProperties = [
            'BaseFare',
            'Cost',
            'Currency',
            'Discount',
            'Tax',
            'Taxes',
            'SpentAwards',
            'EarnedAwards',
            'Total',
            'TotalCharge',
            'TripNumber',
            'ReservationDate',
            'Fees',
            'AccountNumbers',
            'ConfirmationNumbers',
            'Status',
        ];

        switch ($table) {
            case 'TripSegment':
                $propertyList = [
                    'Aircraft',
                    'ArrivalGate',
                    'Gate',
                    'ArrivalTerminal',
                    'DepartureTerminal',
                    'BaggageClaim',
                    'BookingClass',
                    'Cabin',
                    'Duration',
                    'Meal',
                    'Smoking',
                    'Stops',
                    'TraveledMiles',
                    'Seats',
                ];

                break;

            case 'Trip':
                $propertyList = array_merge([
                    'RoomClass',
                    'RoomNumber',
                    'ShipCode',
                    'ShipName',
                    'CruiseName',
                    'Deck',
                    'Seats',
                    'Passengers',
                    'TicketNumbers',
                ], $itineraryProperties);

                break;

            case 'Reservation':
                $propertyList = array_merge([
                    'CancellationPolicy',
                    'Fax',
                    'Rooms',
                    'Guests',
                    'Kids',
                    'GuestNames',
                    'RoomType',
                    'RoomTypeDescription',
                    'Rate',
                    'RateType',
                ], $itineraryProperties);

                break;

            case 'Rental':
                $propertyList = array_merge([
                    'CarImageUrl',
                    'CarModel',
                    'CarType',
                    'DropOffFax',
                    'PickUpFax',
                    'RentalCompany',
                    'RenterName',
                    'TravelerNames',
                ], $itineraryProperties);

                break;

            case 'Restaurant':
                $propertyList = array_merge([
                    'Phone',
                    'DinerName',
                    'Guests',
                ], $itineraryProperties);

                break;

            default:
                throw new \LogicException("Invalid table");
        }
        $letter = self::TABLE_TO_LETTER[$table];
        $builder = $this->archiveConnection->createQueryBuilder();
        $builder->select("{$table}ID, " . implode(', ', array_map(function (string $propertyName) {
            return "ExtProperty$propertyName.Value AS ExtProperty{$propertyName}Value";
        }, $propertyList)));
        $builder->from($table);

        foreach ($propertyList as $propertyName) {
            $builder->leftJoin(
                $table,
                "ExtProperty",
                "ExtProperty$propertyName",
                "
                    ExtProperty$propertyName.SourceTable = '{$letter}' 
                    AND ExtProperty$propertyName.SourceID = {$table}ID
                    AND ExtProperty$propertyName.Name = '$propertyName'
                "
            );
        }
        $builder->where("ExtPropertyMerged = FALSE");

        return $builder;
    }

    private function processTripSegment(array $tripSegment)
    {
        $builder = $this->archiveConnection->createQueryBuilder();
        $builder->update('TripSegment');
        $builder->where("TripSegmentID = '{$tripSegment['TripSegmentID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $tripSegmentDirectCopyMap = [
            'Aircraft' => 'Aircraft',
            'ArrivalGate' => 'ArrivalGate',
            'Gate' => 'DepartureGate',
            'ArrivalTerminal' => 'ArrivalTerminal',
            'DepartureTerminal' => 'DepartureTerminal',
            'BaggageClaim' => 'BaggageClaim',
            'Cabin' => 'CabinClass',
            'Duration' => 'Duration',
            'Meal' => 'Meal',
            'Smoking' => 'Smoking',
            'Stops' => 'Stops',
            'TraveledMiles' => 'TraveledMiles',
        ];
        $doUpdate = $this->setDirectCopy($builder, $tripSegmentDirectCopyMap, $tripSegment);

        if (null !== $tripSegment["ExtPropertySeatsValue"]) {
            // Already in doctrine simple_array format except for spaces
            $builder->set("Seats", "REPLACE ('" . addslashes($tripSegment["ExtPropertySeatsValue"]) . "', ' ', '')");
            $doUpdate = true;
        }

        if (null !== $tripSegment["ExtPropertyBookingClassValue"] && strlen($tripSegment["ExtPropertyBookingClassValue"]) < 2) {
            $builder->set("BookingClass", $builder->createNamedParameter($tripSegment["ExtPropertyBookingClassValue"]));
        }
        $builder->execute();

        if ($doUpdate) {
            return true;
        } else {
            return false;
        }
    }

    private function setDirectCopy(QueryBuilder $builder, array $map, array $row)
    {
        $doUpdate = false;

        foreach ($map as $propertyName => $destination) {
            if (null === $row["ExtProperty{$propertyName}Value"]) {
                continue;
            }
            $builder->set($destination, $builder->createNamedParameter($row["ExtProperty{$propertyName}Value"]));
            $doUpdate = true;
        }

        return $doUpdate;
    }

    private function processTrip(array $trip)
    {
        $builder = $this->archiveConnection->createQueryBuilder();
        $builder->update('Trip');
        $builder->where("TripID = '{$trip['TripID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $tripDirectCopyMap = [
            'RoomClass' => 'CabinClass',
            'RoomNumber' => 'CabinNumber',
            'ShipCode' => 'ShipCode',
            'ShipName' => 'ShipName',
            'CruiseName' => 'CruiseName',
            'Deck' => 'Deck',
        ];
        $doUpdate = $this->setDirectCopy($builder, $tripDirectCopyMap, $trip);

        if (null !== $trip["ExtPropertyPassengersValue"]) {
            // Already in doctrine simple_array format except for spaces
            $builder->set("TravelerNames",
                $builder->createNamedParameter(str_replace(', ', ',', $trip["ExtPropertyPassengersValue"])));
            $doUpdate = true;
        }

        if (null !== $trip["ExtPropertyTicketNumbersValue"]) {
            // Already in doctrine simple_array format except for spaces
            $builder->set("TicketNumbers",
                $builder->createNamedParameter(str_replace(', ', ',', $trip["ExtPropertyTicketNumbersValue"])));
            $doUpdate = true;
        }
        $doUpdate |= $this->addItineraryFields($builder, $trip);
        $builder->execute();

        if ($doUpdate) {
            return true;
        } else {
            return false;
        }
    }

    private function addItineraryFields(QueryBuilder $builder, array $itinerary)
    {
        $directCopyMap = [
            'BaseFare' => 'Cost',
            'Cost' => 'Cost',
            'Currency' => 'CurrencyCode',
            'Discount' => 'Discount',
            'SpentAwards' => 'SpentAwards',
            'EarnedAwards' => 'EarnedAwards',
            'Total' => 'Total',
            'TotalCharge' => 'Total',
            'TripNumber' => 'TravelAgencyConfirmationNumbers',
            'Status' => 'ParsedStatus',
        ];
        $doUpdate = $this->setDirectCopy($builder, $directCopyMap, $itinerary);

        if (null !== $itinerary['ExtPropertyReservationDateValue']) {
            $builder->set('ReservationDate',
                "'" . date('Y-m-d H:i:s', (int) $itinerary['ExtPropertyReservationDateValue']) . "'");
            $doUpdate = true;
        }
        $fees = null;

        if (null !== $itinerary['ExtPropertyFeesValue'] || null !== $itinerary['ExtPropertyTaxValue'] || $itinerary['ExtPropertyTaxesValue']) {
            $fees = [];

            if (null !== $itinerary['ExtPropertyFeesValue']) {
                $unserializedFees = unserialize($itinerary['ExtPropertyFeesValue']);

                foreach ($unserializedFees as $unserializedFee) {
                    if (!isset($unserializedFee['Name']) || !isset($unserializedFee['Charge'])) {
                        continue;
                    }

                    if (!is_numeric($unserializedFee['Charge'])) {
                        continue;
                    }
                    $fees[] = new Fee((string) $unserializedFee['Name'], (float) $unserializedFee['Charge']);
                }
            }

            if (null !== $itinerary['ExtPropertyTaxValue'] || null !== $itinerary['ExtPropertyTaxesValue']) {
                $taxValue = $itinerary['ExtPropertyTaxValue'] ?? $itinerary['ExtPropertyTaxesValue'];

                if (is_numeric($taxValue)) {
                    $fees[] = new Fee('Tax', (float) $taxValue);
                }
            }

            if (!empty($fees)) {
                $builder->set('Fees', $builder->createNamedParameter(serialize($fees)));
            }
        }

        if (null !== $itinerary['ExtPropertyAccountNumbersValue']) {
            // Already in doctrine simple_array format except for spaces
            $builder->set("ParsedAccountNumbers",
                $builder->createNamedParameter(str_replace(' ', '', $itinerary["ExtPropertyAccountNumbersValue"])));
            $doUpdate = true;
        }

        if (null !== $itinerary['ExtPropertyConfirmationNumbersValue']) {
            // Already in doctrine simple_array format except for spaces
            $builder->set("ConfirmationNumbers", $builder->createNamedParameter(str_replace(' ', '',
                $itinerary["ExtPropertyConfirmationNumbersValue"])));
            $doUpdate = true;
        }

        if (null !== $itinerary['ExtPropertyCurrencyValue']) {
            $builder->set("CurrencyCode", $builder->createNamedParameter(str_replace(' ', '',
                $itinerary["ExtPropertyCurrencyValue"])));
            $doUpdate = true;
        }

        return $doUpdate;
    }

    private function processReservation(array $reservation)
    {
        $builder = $this->archiveConnection->createQueryBuilder();
        $builder->update('Reservation');
        $builder->where("ReservationID = '{$reservation['ReservationID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $reservationDirectCopyMap = [
            'CancellationPolicy' => 'CancellationPolicy',
            'Fax' => 'Fax',
            'Rooms' => 'RoomCount',
        ];
        $doUpdate = $this->setDirectCopy($builder, $reservationDirectCopyMap, $reservation);

        if (null !== $reservation["ExtPropertyGuestsValue"]) {
            // Can be '1|1' or '2|2' instead of numeric
            $numbers = explode('|', $reservation["ExtPropertyGuestsValue"]);
            $guestCount = array_sum($numbers);
            $builder->set('GuestCount', $builder->createNamedParameter($guestCount));
            $doUpdate = true;
        }

        if (null !== $reservation["ExtPropertyKidsValue"]) {
            // Can be '1|1' or '2|2' instead of numeric
            $numbers = explode('|', $reservation["ExtPropertyKidsValue"]);

            if (!empty(array_filter($numbers, function (string $number) {
                return is_numeric($number);
            }))) {
                $kidsCount = array_sum($numbers);
                $builder->set('KidsCount', $builder->createNamedParameter($kidsCount));
                $doUpdate = true;
            }
        }

        if (null !== $reservation["ExtPropertyGuestNamesValue"]) {
            // Already in doctrine simple_array format except for spaces
            $builder->set("TravelerNames",
                $builder->createNamedParameter(str_replace(', ', ',', $reservation["ExtPropertyGuestNamesValue"])));
            $doUpdate = true;
        }

        // Type is a required field
        if (null !== $reservation["ExtPropertyRoomTypeValue"]) {
            $shortDescriptionValues = explode(' | ', $reservation["ExtPropertyRoomTypeValue"]);
            $descriptionValues = [];
            $rateValues = [];
            $rateDescriptionValues = [];

            if (null !== $reservation["ExtPropertyRoomTypeDescriptionValue"]) {
                $descriptionValues = explode(' | ', $reservation["ExtPropertyRoomTypeDescriptionValue"]);
            }

            if (null !== $reservation["ExtPropertyRateValue"]) {
                $rateValues = explode(' | ', $reservation["ExtPropertyRateValue"]);
            }

            if (null !== $reservation["ExtPropertyRateTypeValue"]) {
                $rateDescriptionValues = explode(' | ', $reservation["ExtPropertyRateTypeValue"]);
            }
            $rooms = [];
            $count = count($shortDescriptionValues);

            for ($i = 0; $i < $count; $i++) {
                $rooms[] = new Room(
                    $shortDescriptionValues[$i],
                    $descriptionValues[$i] ?? null,
                    $rateValues[$i] ?? null,
                    $rateDescriptionValues[$i] ?? null
                );
            }
            $builder->set('Rooms', $builder->createNamedParameter(serialize($rooms)));
            $doUpdate = true;
        }
        $doUpdate |= $this->addItineraryFields($builder, $reservation);
        $builder->execute();

        if ($doUpdate) {
            return true;
        } else {
            return false;
        }
    }

    private function processRental(array $rental)
    {
        $builder = $this->archiveConnection->createQueryBuilder();
        $builder->update('Rental');
        $builder->where("RentalID = '{$rental['RentalID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $rentalDirectCopyMap = [
            'CarImageUrl' => 'CarImageUrl',
            'CarModel' => 'CarModel',
            'CarType' => 'CarType',
            'DropOffFax' => 'DropOffFax',
            'PickUpFax' => 'PickUpFax',
            'RentalCompany' => 'ProviderName',
            'RenterName' => 'TravelerNames',
        ];
        $doUpdate = $this->setDirectCopy($builder, $rentalDirectCopyMap, $rental);
        $doUpdate |= $this->addItineraryFields($builder, $rental);
        $builder->execute();

        if ($doUpdate) {
            return true;
        } else {
            return false;
        }
    }

    private function processRestaurant(array $restaurant)
    {
        $builder = $this->archiveConnection->createQueryBuilder();
        $builder->update('Restaurant');
        $builder->where("RestaurantID = '{$restaurant['RestaurantID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $tripDirectCopyMap = [
            'Phone' => 'Phone',
        ];
        $doUpdate = $this->setDirectCopy($builder, $tripDirectCopyMap, $restaurant);

        if (null !== $restaurant["ExtPropertyGuestsValue"]) {
            // Can be '1|1' or '2|2' instead of numeric
            $numbers = explode('|', $restaurant["ExtPropertyGuestsValue"]);
            $guestCount = array_sum($numbers);
            $builder->set('GuestCount', $builder->createNamedParameter($guestCount));
            $doUpdate = true;
        }

        if (null !== $restaurant["ExtPropertyDinerNameValue"]) {
            $builder->set("TravelerNames",
                $builder->createNamedParameter(str_replace(', ', ',', $restaurant["ExtPropertyDinerNameValue"])));
            $doUpdate = true;
        }
        $doUpdate |= $this->addItineraryFields($builder, $restaurant);
        $builder->execute();

        if ($doUpdate) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseUpdateTable(string $table)
    {
        if ('TripSegment' === $table) {
            $count = $this->archiveConnection->executeQuery("SELECT count(*) AS cnt FROM $table WHERE ChangeDate >= '" . self::MIGRATION_DATE . "'")->fetchColumn(0);
        } else {
            $count = $this->archiveConnection->executeQuery("SELECT count(*) AS cnt FROM $table WHERE UpdateDate >= '" . self::MIGRATION_DATE . "'")->fetchColumn(0);
        }
        $this->write("Processing $table, $count rows:");
        $builder = $this->archiveConnection->createQueryBuilder();
        $builder->select('*');
        $builder->from($table);

        if ('TripSegment' === $table) {
            $builder->where("ChangeDate >= '" . self::MIGRATION_DATE . "'");
        } else {
            $builder->where("UpdateDate >= '" . self::MIGRATION_DATE . "'");
        }
        $processedCounter = 0;
        $insertedCounter = 0;

        do {
            $timestamp = time();
            $builder->setMaxResults(self::BATCH_SIZE);
            $builder->setFirstResult($processedCounter);
            $statement = $builder->execute();

            while ($nextRow = $statement->fetch(\PDO::FETCH_ASSOC)) {
                switch ($table) {
                    case 'TripSegment':
                        $insertedCounter += $this->reverseProcessTripSegment($nextRow);

                        break;

                    case 'Trip':
                        $insertedCounter += $this->reverseProcessTrip($nextRow);

                        break;

                    case 'Reservation':
                        $insertedCounter += $this->reverseProcessReservation($nextRow);

                        break;

                    case 'Rental':
                        $insertedCounter += $this->reverseProcessRental($nextRow);

                        break;

                    case 'Restaurant':
                        $insertedCounter += $this->reverseProcessRestaurant($nextRow);

                        break;

                    default:
                        throw new \LogicException("Invalid table");
                }
                $processedCounter++;

                if (0 === $processedCounter % self::BATCH_SIZE) {
                    $timer = time() - $timestamp;
                    $this->write("{$insertedCounter}/{$processedCounter}/{$count} ({$timer}s)");
                }
            }
            $this->archiveConnection->commit();
            $this->archiveConnection->beginTransaction();
        } while ($statement->rowCount());
        $this->write("{$insertedCounter}/{$processedCounter}/{$count}");
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseProcessTripSegment(array $tripSegment): int
    {
        $tripSegmentDirectCopyMap = [
            'Aircraft' => 'Aircraft',
            'ArrivalGate' => 'ArrivalTerminal',
            'Gate' => 'DepartureGate',
            'ArrivalTerminal' => 'ArrivalTerminal',
            'DepartureTerminal' => 'DepartureTerminal',
            'BaggageClaim' => 'BaggageClaim',
            'Cabin' => 'CabinClass',
            'Duration' => 'Duration',
            'Meal' => 'Meal',
            'Smoking' => 'Smoking',
            'Stops' => 'Stops',
            'TraveledMiles' => 'TraveledMiles',
            'BookingClass' => 'BookingClass',
        ];
        $counter = $this->reverseDirectCopy($tripSegmentDirectCopyMap, $tripSegment, 'TripSegment');
        $letter = self::TABLE_TO_LETTER['TripSegment'];
        $entityId = $tripSegment["TripSegmentID"];

        if (!empty($tripSegment["Seats"])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Seats', REPLACE(?, ',', ', '))", [$letter, $entityId, $tripSegment['Seats']]);
            $counter++;
        }

        return $counter;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseDirectCopy(array $map, array $row, string $table): int
    {
        $counter = 0;
        $letter = self::TABLE_TO_LETTER[$table];
        $entityId = $row["{$table}ID"];

        foreach ($map as $extPropertyName => $itineraryFieldName) {
            if (!isset($row[$itineraryFieldName]) || empty($row[$itineraryFieldName])) {
                continue;
            }
            $value = $row[$itineraryFieldName];
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, ?, ?)", [$letter, $entityId, $extPropertyName, $value]);
            $counter++;
        }

        return $counter;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseProcessTrip(array $trip): int
    {
        $this->reverseProcessItinerary($trip, 'Trip');
        $tripDirectCopyMap = [
            'BaseFare' => 'Cost',
            'TotalCharge' => 'Total',
            'RoomClass' => 'CabinClass',
            'RoomNumber' => 'CabinNumber',
            'ShipCode' => 'ShipCode',
            'ShipName' => 'ShipName',
            'CruiseName' => 'CruiseName',
            'Deck' => 'Deck',
        ];
        $counter = $this->reverseDirectCopy($tripDirectCopyMap, $trip, 'Trip');
        $letter = self::TABLE_TO_LETTER['Trip'];
        $entityId = $trip["TripID"];

        if (!empty($trip["TravelerNames"])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Passengers', REPLACE(?, ',', ', '))", [$letter, $entityId, $trip['TravelerNames']]);
            $counter++;
        }

        return $counter;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseProcessItinerary(array $itinerary, string $table): int
    {
        $directCopyMap = [
            'Currency' => 'CurrencyCode',
            'Discount' => 'Discount',
            'SpentAwards' => 'SpentAwards',
            'EarnedAwards' => 'EarnedAwards',
            'TripNumber' => 'TravelAgencyConfirmationNumbers',
            'ParsedStatus' => 'Status',
        ];
        $counter = $this->reverseDirectCopy($directCopyMap, $itinerary, $table);
        $letter = self::TABLE_TO_LETTER[$table];
        $entityId = $itinerary["{$table}ID"];

        if (!empty($itinerary["ReservationDate"])) {
            $timestamp = strtotime($itinerary['ReservationDate']);
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'ReservationDate', ?)", [$letter, $entityId, $timestamp]);
            $counter++;
        }

        if (null !== $itinerary['Fees']) {
            $fees = unserialize($itinerary['Fees']);
        } else {
            $fees = null;
        }

        if (!empty($fees)) {
            $feesArray = [];

            /** @var Fee $fee */
            foreach ($fees as $fee) {
                $feesArray[] = ['Name' => $fee->getName(), 'Charge' => $fee->getCharge()];
            }
            $serializedFeesArray = serialize($feesArray);
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Fees', ?)", [$letter, $entityId, $serializedFeesArray]);
            $counter++;
        }

        if (!empty($itinerary['ParsedAccountNumbers'])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'AccountNumbers', REPLACE(?, ',', ', '))", [$letter, $entityId, $itinerary['ParsedAccountNumbers']]);
            $counter++;
        }

        if (!empty($itinerary['ConfirmationNumbers'])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'ConfirmationNumbers', REPLACE(?, ',', ', '))", [$letter, $entityId, $itinerary['ConfirmationNumbers']]);
            $counter++;
        }

        if (!empty($itinerary['CurrencyCode'])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Currency', REPLACE(?, ',', ', '))", [$letter, $entityId, $itinerary['CurrencyCode']]);
            $counter++;
        }

        return $counter;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseProcessReservation(array $reservation): int
    {
        $this->reverseProcessItinerary($reservation, 'Reservation');
        $reservationDirectCopyMap = [
            'Cost' => 'Cost',
            'Total' => 'Total',
            'CancellationPolicy' => 'CancellationPolicy',
            'Fax' => 'Fax',
            'Rooms' => 'RoomCount',
        ];
        $counter = $this->reverseDirectCopy($reservationDirectCopyMap, $reservation, 'Reservation');
        $letter = self::TABLE_TO_LETTER['Reservation'];
        $entityId = $reservation["ReservationID"];

        if (!empty($reservation["TravelerNames"])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'GuestNames', REPLACE(?, ',', ', '))", [$letter, $entityId, $reservation['TravelerNames']]);
            $counter++;
        }

        if (!empty($reservation["GuestCount"])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Guests', ?)", [$letter, $entityId, $reservation['GuestCount']]);
            $counter++;
        }

        if (!empty($reservation["KidsCount"])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Kids', ?)", [$letter, $entityId, $reservation['KidsCount']]);
            $counter++;
        }
        $rooms = unserialize($reservation['Rooms']);
        $shortDescriptions = [];
        $longDescriptions = [];
        $rates = [];
        $rateDescriptions = [];

        /** @var Room $room */
        foreach ($rooms as $room) {
            $shortDescriptions[] = $room->getShortDescription();

            if (null !== $room->getLongDescription()) {
                $longDescriptions[] = $room->getLongDescription();
            }

            if (null !== $room->getRate()) {
                $rates[] = $room->getRate();
            }

            if (null !== $room->getRateDescription()) {
                $rateDescriptions[] = $room->getRateDescription();
            }
        }

        if (!empty($shortDescriptions)) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'RoomType', ?)", [$letter, $entityId, implode('|', $shortDescriptions)]);
            $counter++;
        }

        if (!empty($longDescriptions)) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'RoomTypeDescription', ?)", [$letter, $entityId, implode('|', $longDescriptions)]);
            $counter++;
        }

        if (!empty($rates)) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Rate', ?)", [$letter, $entityId, implode('|', $rates)]);
            $counter++;
        }

        if (!empty($rateDescriptions)) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'RateType', ?)", [$letter, $entityId, implode('|', $rateDescriptions)]);
            $counter++;
        }

        return $counter;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseProcessRental(array $rental): int
    {
        $this->reverseProcessItinerary($rental, 'Rental');
        $rentalDirectCopyMap = [
            'BaseFare' => 'Cost',
            'TotalCharge' => 'Total',
            'CarImageUrl' => 'CarImageUrl',
            'CarModel' => 'CarModel',
            'CarType' => 'CarType',
            'DropOffFax' => 'DropOffFax',
            'PickUpFax' => 'PickUpFax',
            'ProviderName' => 'RentalCompany',
            'RenterName' => 'TravelerNames',
        ];
        $counter = $this->reverseDirectCopy($rentalDirectCopyMap, $rental, 'Rental');

        return $counter;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function reverseProcessRestaurant(array $restaurant): int
    {
        $this->reverseProcessItinerary($restaurant, 'Restaurant');
        $restaurantDirectCopyMap = [
            'Cost' => 'Cost',
            'TotalCharge' => 'Total',
            'Phone' => 'Phone',
        ];
        $counter = $this->reverseDirectCopy($restaurantDirectCopyMap, $restaurant, 'Restaurant');
        $letter = self::TABLE_TO_LETTER['Restaurant'];
        $entityId = $restaurant["RestaurantID"];

        if (!empty($restaurant["GuestCount"])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'Guests', ?)", [$letter, $entityId, $restaurant['GuestCount']]);
            $counter++;
        }

        if (!empty($restaurant["TravelerNames"])) {
            $this->archiveConnection->executeUpdate("INSERT IGNORE INTO ExtProperty (SourceTable, SourceID, Name, Value) VALUES (?, ?, 'DinerName', REPLACE(?, ',', ', '))", [$letter, $entityId, $restaurant['TravelerNames']]);
            $counter++;
        }

        return $counter;
    }
}
