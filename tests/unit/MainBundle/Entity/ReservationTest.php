<?php

namespace AwardWallet\Tests\Unit\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 */
class ReservationTest extends BaseTest
{
    /**
     * @dataProvider nightsDataProvider
     */
    public function testNights(string $checkIn, string $checkOut, int $nights)
    {
        $reservation = new Reservation();
        $reservation->setCheckindate(new \DateTime($checkIn));
        $reservation->setCheckoutdate(new \DateTime($checkOut));
        $this->assertEquals($nights, $reservation->getNights());
    }

    public function nightsDataProvider()
    {
        return [
            ['2017-01-01 14:00', '2017-01-02 11:00', 1],
            ['2017-01-02 2:00', '2017-01-02 11:00', 1],
            ['2017-01-02 2:00', '2017-01-02 3:00', 1],
            ['2017-01-02 2:00', '2017-01-02 2:00', 1],
            ['2017-01-01 2:00', '2017-01-02 2:00', 2],
            ['2017-01-01 2:00', '2017-01-03 2:00', 3],
            ['2017-01-01 14:00', '2017-01-03 11:00', 2],
        ];
    }
}
