<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Reservation as EntityReservation;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\ReservationMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaReservation;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class ReservationMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var ReservationMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        $this->matcher = new ReservationMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
    }

    public function testSupports()
    {
        /** @var SchemaReservation $schemaReservation */
        $schemaReservation = new SchemaReservation();
        /** @var EntityReservation $entityReservation */
        $entityReservation = new EntityReservation();
        $invalidEntity = new class() extends EntityItinerary {
            public function getStartDate()
            {
            }

            public function getEndDate()
            {
            }

            public function getUTCStartDate()
            {
            }

            public function getUTCEndDate()
            {
            }

            public function getPhones()
            {
            }

            public function getGeoTags()
            {
            }

            public function getType(): string
            {
                return '';
            }

            public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
            {
                return [];
            }

            public function getKind(): string
            {
                return 'R';
            }
        };
        $this->assertTrue($this->matcher->supports($entityReservation, $schemaReservation));
        $this->assertFalse($this->matcher->supports($entityReservation, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaReservation));
    }

    public function testUpdateWithWrongEntityType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidEntity = new class() extends EntityItinerary {
            public function getStartDate()
            {
            }

            public function getEndDate()
            {
            }

            public function getUTCStartDate()
            {
            }

            public function getUTCEndDate()
            {
            }

            public function getPhones()
            {
            }

            public function getGeoTags()
            {
            }

            public function getType(): string
            {
                return '';
            }

            public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
            {
                return [];
            }

            public function getKind(): string
            {
                return 'R';
            }
        };
        $this->matcher->match($invalidEntity, new SchemaReservation());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityReservation(), new Cruise());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        ?string $schemaConfNo,
        ?string $entityConfNo,
        \DateTime $checkInDate,
        \DateTime $checkOutDate,
        string $hotelName = 'Same hotel'
    ) {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => $schemaConfNo,
        ]);
        $matcher = new ReservationMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
        /** @var EntityReservation $entityReservation */
        $entityReservation = $this->makeEmpty(EntityReservation::class, [
            'getConfirmationNumber' => $entityConfNo,
            'getCheckindate' => $checkInDate,
            'getCheckoutdate' => $checkOutDate,
            'getHotelname' => $hotelName,
        ]);
        /** @var SchemaReservation $schemaReservation */
        $schemaReservation = $this->getSchemaReservation();
        $this->assertSame($expectedConfidence, $matcher->match($entityReservation, $schemaReservation));
    }

    public function dataProvider()
    {
        $sameConfirmationNumber = 'same_number';
        $differentConfirmationNumber = 'different_number';

        $sameCheckInDate = new \DateTime('+1 day 12:00');
        $differentCheckInDate = new \DateTime('+2 days');

        $sameCheckOutDate = new \DateTime('+1 day 16:00');
        $differentCheckOutDate = new \DateTime('+2 days');

        $sameHotelName = 'Same hotel';
        $differentHotelName = 'Diff hotel';

        return [
            [.99, $sameConfirmationNumber, $sameConfirmationNumber, $differentCheckInDate, $differentCheckOutDate],
            [.99, $sameConfirmationNumber, strtoupper($sameConfirmationNumber), $differentCheckInDate, $differentCheckOutDate],
            [.97, null, null, $sameCheckInDate, $sameCheckOutDate],
            [.00, $sameConfirmationNumber, null, $sameCheckInDate, $sameCheckOutDate, $differentHotelName],
            [.97, $sameConfirmationNumber, null, $sameCheckInDate, $sameCheckOutDate, $sameHotelName],
            [.00, $sameConfirmationNumber, $differentConfirmationNumber, $differentCheckInDate, $sameCheckOutDate],
            [.97, $sameConfirmationNumber, null, $sameCheckInDate, $sameCheckOutDate],
            [.97, null, $sameConfirmationNumber, $sameCheckInDate, $sameCheckOutDate],
            [.00, null, $sameConfirmationNumber, $sameCheckInDate, $sameCheckOutDate, $differentHotelName],
        ];
    }

    private function getSchemaReservation(): SchemaReservation
    {
        $reservation = new SchemaReservation();
        $reservation->checkInDate = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $reservation->checkOutDate = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));
        $reservation->confirmationNumbers = [new ConfNo()];
        $reservation->confirmationNumbers[0]->number = 'same_number';
        $reservation->confirmationNumbers[0]->isPrimary = true;
        $reservation->hotelName = 'Same hotel';

        return $reservation;
    }
}
