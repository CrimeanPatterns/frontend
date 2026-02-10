<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Entity\Room;
use Doctrine\DBAL\Migrations\IrreversibleMigrationException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180405090703 extends AbstractMigration
{
    public const BATCH_SIZE = 100000;

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        foreach (['TripSegment', 'Trip', 'Reservation', 'Rental', 'Restaurant'] as $table) {
            $this->updateTable($table);
        }

        //TODO maybe save as a text field too?
        $this->addSql("
            UPDATE TripSegment 
            JOIN ExtProperty ON ExtProperty.SourceID = TripSegmentID AND ExtProperty.Name = 'Aircraft' 
            LEFT JOIN Aircraft AircraftByIata ON ExtProperty.Value = AircraftByIata.IataCode COLLATE utf8_general_ci 
            LEFT JOIN Aircraft AircraftByName ON ExtProperty.Value = AircraftByName.Name COLLATE utf8_general_ci 
            LEFT JOIN Aircraft AircraftByShortName ON ExtProperty.Value = AircraftByShortName.ShortName COLLATE utf8_general_ci 
            SET TripSegment.AircraftID = COALESCE(AircraftByIata.AircraftID, AircraftByName.AircraftID, AircraftByShortName.AircraftID) 
        ");
    }

    /**
     * @throws IrreversibleMigrationException
     */
    public function down(Schema $schema): void
    {
//        $this->throwIrreversibleMigrationException();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateTable(string $table)
    {
        $count = $this->connection->executeQuery("SELECT count(*) AS cnt FROM $table WHERE ExtPropertyMerged = FALSE")->fetchColumn(0);
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
            $this->connection->commit();
            $this->connection->beginTransaction();
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
                    'ProviderName',
                    'RenterName',
                    'TravelerNames',
                ], $itineraryProperties);

                break;

            case 'Restaurant':
                $propertyList = array_merge([
                    'Phone',
                    'DinerName',
                ], $itineraryProperties);

                break;

            default:
                throw new \LogicException("Invalid table");
        }
        $tableToLetter = [
            'TripSegment' => 'S',
            'Trip' => 'T',
            'Reservation' => 'R',
            'Rental' => 'L',
            'Restaurant' => 'E',
        ];
        $builder = $this->connection->createQueryBuilder();
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
                    ExtProperty$propertyName.SourceTable = '{$tableToLetter[$table]}' 
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
        $builder = $this->connection->createQueryBuilder();
        $builder->update('TripSegment');
        $builder->where("TripSegmentID = '{$tripSegment['TripSegmentID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
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
        ];
        $doUpdate = $this->setDirectCopy($builder, $tripSegmentDirectCopyMap, $tripSegment);

        if (null !== $tripSegment["ExtPropertySeatsValue"]) {
            //Already in doctrine simple_array format except for spaces
            $builder->set("Seats", "REPLACE ('{$tripSegment["ExtPropertySeatsValue"]}', ' ', '')");
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

    private function processTrip(array $trip)
    {
        $builder = $this->connection->createQueryBuilder();
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

        if (null !== $trip["ExtPropertySeatsValue"]) {
            //Already in doctrine simple_array format except for spaces
            $builder->set("Seats",
                $builder->createNamedParameter(str_replace(', ', ',', $trip["ExtPropertySeatsValue"])));
            $doUpdate = true;
        }

        if (null !== $trip["ExtPropertyPassengersValue"]) {
            //Already in doctrine simple_array format except for spaces
            $builder->set("TravelerNames",
                $builder->createNamedParameter(str_replace(', ', ',', $trip["ExtPropertyPassengersValue"])));
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

    private function processReservation(array $reservation)
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->update('Reservation');
        $builder->where("ReservationID = '{$reservation['ReservationID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $tripDirectCopyMap = [
            'CancellationPolicy' => 'CancellationPolicy',
            'Fax' => 'Fax',
            'Rooms' => 'RoomCount',
        ];
        $doUpdate = $this->setDirectCopy($builder, $tripDirectCopyMap, $reservation);

        if (null !== $reservation["ExtPropertyGuestsValue"]) {
            //Can be '1|1' or '2|2' instead of numeric
            $numbers = explode('|', $reservation["ExtPropertyGuestsValue"]);
            $guestCount = array_sum($numbers);
            $builder->set('GuestCount', $builder->createNamedParameter($guestCount));
            $doUpdate = true;
        }

        if (null !== $reservation["ExtPropertyKidsValue"]) {
            //Can be '1|1' or '2|2' instead of numeric
            $numbers = explode('|', $reservation["ExtPropertyKidsValue"]);
            $kidsCount = array_sum($numbers);
            $builder->set('KidsCount', $builder->createNamedParameter($kidsCount));
            $doUpdate = true;
        }

        if (null !== $reservation["ExtPropertyGuestNamesValue"]) {
            //Already in doctrine simple_array format except for spaces
            $builder->set("TravelerNames",
                $builder->createNamedParameter(str_replace(', ', ',', $reservation["ExtPropertyGuestNamesValue"])));
            $doUpdate = true;
        }
        //Type is a required field
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
        $builder = $this->connection->createQueryBuilder();
        $builder->update('Rental');
        $builder->where("RentalID = '{$rental['RentalID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $tripDirectCopyMap = [
            'CarImageUrl' => 'CarImageUrl',
            'CarModel' => 'CarModel',
            'CarType' => 'CarType',
            'DropOffFax' => 'DropOffFax',
            'PickUpFax' => 'PickUpFax',
            'ProviderName' => 'RentalCompany',
            'RenterName' => 'TravelerNames',
        ];
        $doUpdate = $this->setDirectCopy($builder, $tripDirectCopyMap, $rental);
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
        $builder = $this->connection->createQueryBuilder();
        $builder->update('Restaurant');
        $builder->where("RestaurantID = '{$restaurant['RestaurantID']}'");
        $builder->set('ExtPropertyMerged', $builder->createNamedParameter(true, \PDO::PARAM_BOOL));
        $tripDirectCopyMap = [
            'Phone' => 'Phone',
        ];
        $doUpdate = $this->setDirectCopy($builder, $tripDirectCopyMap, $restaurant);

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

    private function addItineraryFields(QueryBuilder $builder, array $itinerary)
    {
        $directCopyMap = [
            'BaseFare' => 'Cost',
            'Cost' => 'Cost',
            'Currency' => 'CurrencyCode',
            'Discount' => 'Discount',
            'Tax' => 'Tax',
            'Taxes' => 'Tax',
            'SpentAwards' => 'SpentAwards',
            'EarnedAwards' => 'EarnedAwards',
            'Total' => 'Total',
            'TotalCharge' => 'Total',
            'TripNumber' => 'TravelAgencyConfirmationNumbers',
        ];
        $doUpdate = $this->setDirectCopy($builder, $directCopyMap, $itinerary);

        if (null !== $itinerary['ExtPropertyReservationDateValue']) {
            $builder->set('ReservationDate',
                "'" . date('Y-m-d H:i:s', $itinerary['ExtPropertyReservationDateValue']) . "'");
            $doUpdate = true;
        }
        $fees = null;

        if (null !== $itinerary['ExtPropertyFeesValue']) {
            $fees = [];
            $unserializedFees = unserialize($itinerary['ExtPropertyFeesValue']);

            foreach ($unserializedFees as $unserializedFee) {
                if (!isset($unserializedFee['Name']) || !isset($unserializedFee['Charge'])) {
                    continue;
                }

                if (!is_numeric($unserializedFee['Charge'])) {
                    continue;
                }
                $fees[] = new Fee($unserializedFee['Name'], $unserializedFee['Charge']);
            }

            if (!empty($fees)) {
                $builder->set('Fees', $builder->createNamedParameter(serialize($fees)));
            }
        }

        if (null !== $itinerary['ExtPropertyAccountNumbersValue']) {
            //Already in doctrine simple_array format except for spaces
            $builder->set("ParsedAccountNumbers",
                $builder->createNamedParameter(str_replace(' ', '', $itinerary["ExtPropertyAccountNumbersValue"])));
            $doUpdate = true;
        }

        if (null !== $itinerary['ExtPropertyConfirmationNumbersValue']) {
            //Already in doctrine simple_array format except for spaces
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
}
