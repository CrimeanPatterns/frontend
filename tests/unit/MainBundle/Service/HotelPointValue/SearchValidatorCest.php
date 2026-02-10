<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\HotelPointValue;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Service\HotelPointValue\SearchValidator;
use Codeception\Example;
use Psr\Log\NullLogger;

/**
 * @covers \AwardWallet\MainBundle\Service\HotelPointValue\SearchValidator
 * @group frontend-unit
 */
class SearchValidatorCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dateProvider
     */
    public function reservationLessThan2DaysAgo(\TestSymfonyGuy $I, Example $example): void
    {
        $reservation = new Reservation();
        $reservation->setFirstSeenDate(new \DateTime($example['firstSeenDate']));
        $reservation->setCheckindate(new \DateTime($example['checkinDate']));

        if (isset($example['reservationDate'])) {
            $reservation->setReservationDate(new \DateTime($example['reservationDate']));
        }

        $geotag = new Geotag();
        $geotag->setTimeZoneLocation('UTC');
        $reservation->setGeoTag($geotag);

        $searchValidator = new SearchValidator(new NullLogger());
        $I->assertEquals($example['expectedResult'], $searchValidator->canSearchPrices($reservation));
    }

    private function dateProvider(): array
    {
        return [
            ['firstSeenDate' => '-1 day', 'checkinDate' => '+1 day', 'expectedResult' => true],
            ['firstSeenDate' => '-1 day', 'reservationDate' => '-35 day', 'checkinDate' => '+1 day', 'expectedResult' => false],
            ['firstSeenDate' => '-35 day', 'checkinDate' => '+900 day', 'expectedResult' => false],
            ['firstSeenDate' => '-3 day', 'checkinDate' => '+1 day', 'expectedResult' => false],
            ['firstSeenDate' => '-3 day', 'checkinDate' => '+30 day', 'expectedResult' => true],
        ];
    }
}
