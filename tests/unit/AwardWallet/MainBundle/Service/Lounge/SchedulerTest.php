<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Service\Lounge\Scheduler;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Clock\ClockTest;

use function Duration\days;
use function Duration\seconds;

/**
 * @group frontend-unit
 */
class SchedulerTest extends BaseContainerTest
{
    public function test()
    {
        $clock = new ClockTest(seconds(strtotime('2023-07-05 00:00:00')));
        $scheduler = new Scheduler($clock, new \DateTime('2023-07-05'), 5, 4);
        $this->assertTrue($scheduler->isItsTimeForScan());
        $this->assertEquals(0, $scheduler->getCurrentUpdateIteration());
        $clock->sleep(days(1));
        $this->assertFalse($scheduler->isItsTimeForScan());
        $clock->sleep(days(4));
        $this->assertTrue($scheduler->isItsTimeForScan());
        $this->assertEquals(4, $scheduler->getNumberOfIterations());
        $this->assertEquals(5, $scheduler->getUpdateFrequency());
        $this->assertEquals(1, $scheduler->getCurrentUpdateIteration());
        $this->assertEquals([
            new \DateTime('2023-07-10'),
            new \DateTime('2023-07-15'),
        ], $scheduler->getSchedule(1, 1));
    }
}
