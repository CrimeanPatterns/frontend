<?php

namespace AwardWallet\Tests\Unit\Globals;

use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class DateTimeUtilsTest extends BaseContainerTest
{
    /**
     * @dataProvider areEqualByTimestampProvider
     */
    public function testAreEqualByTimestamp($expected, $dateTimeA, $dateTimeB)
    {
        $time = time();
        $prepareDate = function ($date) use ($time) {
            if (is_string($date)) {
                return new \DateTime('@' . strtotime($date, $time));
            }

            return $date;
        };

        $dateTimeA = $prepareDate($dateTimeA);
        $dateTimeB = $prepareDate($dateTimeB);

        $this->assertEquals($expected, DateTimeUtils::areEqualByTimestamp($dateTimeA, $dateTimeB));
    }

    public function areEqualByTimestampProvider()
    {
        return [
            [true, 'now', 'now'],
            [false, 'now', '-1 second'],
            [false, 'now', null],
            [false, null, 'now'],
            [true, null, null],
        ];
    }
}
