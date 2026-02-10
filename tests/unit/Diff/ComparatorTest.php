<?php

namespace AwardWallet\Tests\Unit\Diff;

use AwardWallet\MainBundle\Service\ItineraryComparator\Comparator;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class ComparatorTest extends BaseContainerTest
{
    protected ?Comparator $comparator;

    public function _before()
    {
        parent::_before();

        $this->comparator = $this->container->get(Comparator::class);
    }

    public function _after()
    {
        $this->comparator = null;

        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(
        bool $expected,
        string $oldValue,
        string $newValue,
        string $name,
        string $kind
    ) {
        $this->assertEquals($expected, $this->comparator->equals($oldValue, $newValue, $name, $kind));
    }

    public function dataProvider()
    {
        return [
            'stops, 1' => [true, '0', '0', PropertiesList::STOPS_COUNT, 'Trip'],
            'stops, 2' => [true, '0', 'Non stop', PropertiesList::STOPS_COUNT, 'Trip'],
            'stops, 3' => [true, 'Non stop', 'Non-Stops', PropertiesList::STOPS_COUNT, 'Trip'],
            'stops, 4' => [true, 'No stops', 'Non-Stops', PropertiesList::STOPS_COUNT, 'Trip'],
            'stops, 5' => [true, 'NON STOPS', '0', PropertiesList::STOPS_COUNT, 'Trip'],

            'compare case' => [true, 'perm', 'PErM', PropertiesList::DEPARTURE_NAME, 'Trip'],

            'duration, 1' => [true, '1hour', '1h 00m', PropertiesList::DURATION, 'Trip'],
            'duration, 2' => [true, '3:30', '3h30m', PropertiesList::DURATION, 'Trip'],
            'duration, 3' => [true, '2h 50m', '2:50', PropertiesList::DURATION, 'Trip'],

            'flight number, 1' => [true, '8642', '8642', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 2' => [true, '8 6 4 2', '8642', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 3' => [true, 'IB8642', '8642', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 4' => [true, 'BA8642', 'IB8642', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 5' => [true, 'BA 8642', 'BA8642', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 6' => [true, 'BA 8642', 'IB8642', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 7' => [false, 'BA8642', 'IB8643', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 8' => [false, 'BA8642', '8643', PropertiesList::FLIGHT_NUMBER, 'Trip'],
            'flight number, 9' => [true, 'flight 123', '123', PropertiesList::FLIGHT_NUMBER, 'Trip'],

            'seats, 1' => [false, '1A', '1b', PropertiesList::SEATS, 'Trip'],
            'seats, 2' => [false, '1C', '5b', PropertiesList::SEATS, 'Trip'],
            'seats, 3' => [false, '1A 3D', '1b', PropertiesList::SEATS, 'Trip'],
            'seats, 4' => [false, '3D', '1b 3D', PropertiesList::SEATS, 'Trip'],
            'seats, 5' => [true, '1A', '1a', PropertiesList::SEATS, 'Trip'],
            'seats, 6' => [true, '1A, 2b', '1a, 2b', PropertiesList::SEATS, 'Trip'],
            'seats, 7' => [true, '1A, 2b', '2b, 1a', PropertiesList::SEATS, 'Trip'],
            'seats, 8' => [true, '1A, 2b, 5C', '2b, 5c, 1a', PropertiesList::SEATS, 'Trip'],
            'seats, 9' => [true, '1A,    2b', '2b,1a', PropertiesList::SEATS, 'Trip'],
            'seats, 10' => [true, '2b, 1a', '2b,1a', PropertiesList::SEATS, 'Trip'],
            'seats, 11' => [true, '2b,1a ,5b', '5b, 2b,1a', PropertiesList::SEATS, 'Trip'],
            'seats, 12' => [true, '2b,1a  ,   5b', '5b,,,, 2b,1a', PropertiesList::SEATS, 'Trip'],
            'seats, 13' => [true, '1A,--', '1a', PropertiesList::SEATS, 'Trip'],
            'seats, 14' => [true, '1A', '1a, ', PropertiesList::SEATS, 'Trip'],
            'seats, 15' => [true, '1A', '1a, --- , -', PropertiesList::SEATS, 'Trip'],
            'seats, 16' => [true, '--', '--- , -', PropertiesList::SEATS, 'Trip'],
            'seats, 17' => [true, '---, 35h, ---', '35H, ---, ---', PropertiesList::SEATS, 'Trip'],
            'seats, 18' => [true, '03A,03B', '3A , 3B', PropertiesList::SEATS, 'Trip'],
            'seats, 19' => [true, '03A , --', '--, 3A', PropertiesList::SEATS, 'Trip'],
            'seats, 20' => [true, '02C , ', ' 2C', PropertiesList::SEATS, 'Trip'],

            'room rate, 1' => [true, '$261.10', '261.10 per Night/per Room', PropertiesList::ROOM_RATE, 'Reservation'],
            'room rate, 2' => [true, '261.1', '261.10 per Night/per Room', PropertiesList::ROOM_RATE, 'Reservation'],
            'room rate, 3' => [false, '261.1', '261.17 per Night/per Room', PropertiesList::ROOM_RATE, 'Reservation'],
            'room rate, 4' => [false, '50.50', '261.17 per Night/per Room', PropertiesList::ROOM_RATE, 'Reservation'],
            'room rate, 5' => [true, '134.10 per Night/per Room', '$134.10', PropertiesList::ROOM_RATE, 'Reservation'],

            'total, 1' => [true, '12750', '12,750 PHP', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 2' => [true, '12750|125.30', '12,750 PHP | 125.30', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 3' => [false, '12750|125.30', '12,750 PHP', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 4' => [true, '125.30|12750', '12,750 PHP | 125.30', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 5' => [true, '125.30|12750', '12,750 PHP | 126.30', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 6' => [false, '125.30|12750', '12,750 PHP | 127.90', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 7' => [true, '0|125.30', '125.3 | 0', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 8' => [true, '0||3', '|3 | 0', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 9' => [true, '5.00', '$5', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 10' => [true, '80000', '80 000', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 11' => [true, '80000', '80 000 POINTS', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 12' => [true, '100', '101', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 13' => [false, '100', '103', PropertiesList::TOTAL_CHARGE, 'Reservation'],
            'total, 14' => [false, '103', '100', PropertiesList::TOTAL_CHARGE, 'Reservation'],

            'cost, 1' => [true, '80000', '80 000 POINTS', PropertiesList::COST, 'Reservation'],
            'cost, 2' => [true, '100', '101', PropertiesList::COST, 'Reservation'],
            'cost, 3' => [false, '100', '103', PropertiesList::COST, 'Reservation'],
            'cost, 4' => [false, '103', '100', PropertiesList::COST, 'Reservation'],
            'cost, 5' => [true, '103|10|10000', '10|10000|103', PropertiesList::COST, 'Reservation'],
            'cost, 6' => [true, '103|10|10000', '10|10100|102', PropertiesList::COST, 'Reservation'],
            'cost, 7' => [false, '103|10|10000', '10|10000|106', PropertiesList::COST, 'Reservation'],

            'cancellation policy' => [true, 'Cancel 30d Prior/Guest Chrd Full Stay/Pts Return | Cancel/Amend 30 Days Piror To Avoid Cancel Charge', 'Cancel/Amend 30 Days Piror To Avoid Cancel Charge | Cancel 30d Prior/Guest Chrd Full Stay/Pts Return', PropertiesList::CANCELLATION_POLICY, 'Reservation'],

            'cabin' => [true, 'Economy', 'Economy/Coach', PropertiesList::FLIGHT_CABIN_CLASS, 'Trip'],
        ];
    }

    /**
     * @dataProvider kindProvider
     */
    public function testCompareTravelerNames(string $kind)
    {
        $this->assertTrue($this->comparator->equals('Mr Jeff L Bianco', 'Jeff L Bianco', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertFalse($this->comparator->equals('Mr Jeff L Bianco', 'Jeff L Bianco, John Bine', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertTrue($this->comparator->equals('John Bine , Jeff L Bianco', 'Jeff L Bianco, John Bine', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertTrue($this->comparator->equals('JENNIFER BIANCO, JEFFREY BIANCO', 'JEFFREY BIANCO, JENNIFER BIANCO', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertTrue($this->comparator->equals('x, y,     z', 'y,x,z', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertFalse($this->comparator->equals('x, y,     z', 'y,z', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertFalse($this->comparator->equals('JENNIFER BIANCO, JEFFREY BIANCO', 'JEFFREY XXXXX, JENNIFER BIANCO', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertTrue($this->comparator->equals(
            'KARL BOTH, MARTIN BOTH, CAROLINE BOTH, SASKIA DIWISCH, KATARINA BOTH',
            'SASKIA DIWISCH, KATARINA BOTH, KARL BOTH, MARTIN BOTH, CAROLINE BOTH',
            PropertiesList::TRAVELER_NAMES, $kind
        ));
        $this->assertTrue($this->comparator->equals('DEAL/STEPHEN', 'Stephen Deal', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertTrue($this->comparator->equals('DEAL/STEPHEN , XXX / YYYY', 'YYYY/XXX, Stephen Deal', PropertiesList::TRAVELER_NAMES, $kind));
        $this->assertTrue(
            $this->comparator->equals(
                'Bretta Goggan',
                'Bretta Jeanne Goggan',
                PropertiesList::TRAVELER_NAMES,
                $kind
            )
        );
        $this->assertTrue(
            $this->comparator->equals(
                'Bretta Jeanne Goggan',
                'Bretta    Goggan',
                PropertiesList::TRAVELER_NAMES,
                $kind
            )
        );
        $this->assertTrue(
            $this->comparator->equals(
                'Bretta Jeanne Goggan',
                'Bretta    Goggan',
                PropertiesList::TRAVELER_NAMES,
                $kind
            )
        );
        $this->assertTrue(
            $this->comparator->equals(
                'John',
                'John Smith',
                PropertiesList::TRAVELER_NAMES,
                $kind
            )
        );
    }

    public function kindProvider()
    {
        return [
            ['Reservation'],
            ['Rental'],
            ['Restaurant'],
            ['Trip'],
            ['Parking'],
        ];
    }

    /**
     * @dataProvider dateTimeProvider
     */
    public function testCompareDateTime($name, $kind)
    {
        $this->assertTrue($this->comparator->equals(mktime(5, 50, 10, 1, 1, 2015), mktime(5, 50, 20, 1, 1, 2015), $name, $kind));
        $this->assertFalse($this->comparator->equals(mktime(5, 50, 10, 1, 1, 2015), mktime(5, 51, 20, 1, 1, 2015), $name, $kind));
        $this->assertFalse($this->comparator->equals(mktime(5, 50, 10, 1, 1, 2015), mktime(6, 50, 20, 1, 1, 2016), $name, $kind));
    }

    public function dateTimeProvider()
    {
        return [
            [PropertiesList::DEPARTURE_DATE, 'Trip'],
            [PropertiesList::ARRIVAL_DATE, 'Trip'],
            [PropertiesList::START_DATE, 'Restaurant'],
            [PropertiesList::END_DATE, 'Restaurant'],
            [PropertiesList::PICK_UP_DATE, 'Rental'],
            [PropertiesList::DROP_OFF_DATE, 'Rental'],
            [PropertiesList::CHECK_IN_DATE, 'Reservation'],
            [PropertiesList::CHECK_OUT_DATE, 'Reservation'],
        ];
    }

    /**
     * @dataProvider dateProvider
     */
    public function testCompareDate($name, $kind)
    {
        $this->assertTrue($this->comparator->equals(mktime(5, 50, 10, 1, 1, 2015), mktime(5, 50, 20, 1, 1, 2015), $name, $kind));
        $this->assertTrue($this->comparator->equals(mktime(1, 20, 30, 1, 1, 2015), mktime(5, 51, 20, 1, 1, 2015), $name, $kind));
        $this->assertTrue($this->comparator->equals(mktime(1, 20, 30, 1, 20, 2016), mktime(5, 51, 20, 1, 20, 2016), $name, $kind));
        $this->assertFalse($this->comparator->equals(mktime(5, 50, 10, 1, 1, 2015), mktime(6, 10, 20, 2, 1, 2016), $name, $kind));
        $this->assertFalse($this->comparator->equals(mktime(5, 50, 10, 1, 1, 2016), mktime(6, 5, 20, 2, 1, 2016), $name, $kind));
    }

    public function dateProvider()
    {
        return [
            [PropertiesList::RESERVATION_DATE, 'Reservation'],
        ];
    }

    /**
     * @dataProvider textProvider
     */
    public function testTextProperties($name, $kind)
    {
        $this->assertTrue($this->comparator->equals(
            'Flexible Rate Please See Terms & Details Link For Cancellation Policy.',
            'Flexible Rate Please See Terms &amp; Details Link For Cancellation Policy.',
            $name, $kind
        ));
    }

    public function textProvider()
    {
        return [
            [PropertiesList::CANCELLATION_POLICY, 'Reservation'],
            [PropertiesList::ROOM_RATE, 'Reservation'],
            [PropertiesList::DEPARTURE_ADDRESS, 'Trip'],
            [PropertiesList::ARRIVAL_ADDRESS, 'Trip'],
            [PropertiesList::ADDRESS, 'Reservation'],
        ];
    }

    public function testAddress()
    {
        $this->container->get("database_connection")->executeUpdate("delete from GeoTag where Address in ('Nesjavellir 801, Nesjavellir, 801 Iceland')");
        $this->assertTrue($this->comparator->equals(
            'Nesjavellir 801, Nesjavellir, 801 Iceland',
            'Nesjavellir 801, Nesjavellir, 801, Iceland&nbsp;',
            PropertiesList::ADDRESS, 'Reservation'
        ));
        $this->assertTrue($this->comparator->equals(
            'Atlanta Airport (ATL)',
            'Atlanta International Airport (ATL)',
            PropertiesList::PICK_UP_LOCATION, 'Rental'
        ));
        $this->assertTrue($this->comparator->equals(
            'Atlanta International Airport (ATL)',
            'Atlanta Airport (ATL)',
            PropertiesList::DROP_OFF_LOCATION, 'Rental'
        ));
    }

    public function testRentalLocation()
    {
        $this->assertTrue($this->comparator->equals(
            'Phx Sky Harbor Intl Airport (PHX)',
            'Phx Sky Harbor Intl Arpt (PHX)',
            PropertiesList::PICK_UP_LOCATION, 'Rental'
        ));
    }

    /**
     * @dataProvider numbersProvider
     */
    public function testCompareNumbers($name, $kind)
    {
        $this->assertTrue($this->comparator->equals("40,000", "40 000", $name, $kind));
        $this->assertTrue($this->comparator->equals("40,000", "40000", $name, $kind));
        $this->assertTrue($this->comparator->equals("40 000", "40K", $name, $kind));
        $this->assertTrue($this->comparator->equals("110K", "110 000", $name, $kind));
        $this->assertFalse($this->comparator->equals("1 night", "1", $name, $kind));
        $this->assertFalse($this->comparator->equals("100 miles", "5 nights", $name, $kind));
        $this->assertFalse($this->comparator->equals("10K", "11K", $name, $kind));
    }

    public function numbersProvider()
    {
        return [
            [PropertiesList::SPENT_AWARDS, 'Reservation'],
            [PropertiesList::EARNED_AWARDS, 'Reservation'],
            [PropertiesList::TRAVELED_MILES, 'Trip'],
        ];
    }

    public function testAccountNumbers()
    {
        $this->assertFalse($this->comparator->equals(
            'AS 123456, AS 123457, AS 123458',
            'AS 123456, AS 123457, -',
            PropertiesList::ACCOUNT_NUMBERS, 'Reservation'
        ));
        $this->assertTrue($this->comparator->equals(
            'AS 123456, AS 123457, AS 123458',
            'AS 123456, AS 123457, AS 123458, -',
            PropertiesList::ACCOUNT_NUMBERS, 'Rental'
        ));
        $this->assertTrue($this->comparator->equals(
            'AS 123456, AS 123457, AS 123458',
            'AS 123457, - , AS 123456, AS 123458',
            PropertiesList::ACCOUNT_NUMBERS, 'Rental'
        ));
        $this->assertFalse($this->comparator->equals(
            'AS 123456, AS 123457, AS 123458',
            'AS 123456, AS 123457',
            PropertiesList::ACCOUNT_NUMBERS, 'Reservation'
        ));
    }
}
