<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Globals\StringUtils;

/**
 * @group frontend-unit
 */
class ThrottlerTest extends BaseContainerTest
{
    public function test1Period()
    {
        $throttler = new \Throttler($this->container->get(\Memcached::class), 1, 2, 2);
        $key = StringUtils::getRandomCode(20);

        $this->waitSecondChanged();
        $this->assertEquals(0, $throttler->getThrottledRequestsCount($key));
        $this->assertEquals(0, $throttler->getDelay($key));
        $this->assertEquals(1, $throttler->getThrottledRequestsCount($key));
        $this->assertEquals(0, $throttler->getThrottledRequestsCount($key . "_other"));

        $this->assertEquals(0, $throttler->getDelay($key));
        $this->assertEquals(2, $throttler->getThrottledRequestsCount($key));

        $this->assertGreaterThan(0, $throttler->getDelay($key));
        $this->assertEquals(3, $throttler->getThrottledRequestsCount($key));

        $this->waitSecondChanged();
        $this->assertEquals(3, $throttler->getThrottledRequestsCount($key));
        $this->assertGreaterThan(0, $throttler->getDelay($key));
        $this->assertEquals(4, $throttler->getThrottledRequestsCount($key));

        $this->waitSecondChanged();
        $this->assertEquals(1, $throttler->getThrottledRequestsCount($key));
        $this->assertEquals(0, $throttler->getDelay($key));
        $this->assertEquals(2, $throttler->getThrottledRequestsCount($key));
    }

    public function testIncrement()
    {
        $throttler = new \Throttler($this->container->get(\Memcached::class), 1, 2, 2);
        $key = StringUtils::getRandomCode(20);

        $this->waitSecondChanged();
        $this->assertEquals(0, $throttler->getThrottledRequestsCount($key));
        $throttler->increment($key);
        $this->assertEquals(1, $throttler->getThrottledRequestsCount($key));
        $throttler->increment($key, 3);
        $this->assertEquals(4, $throttler->getThrottledRequestsCount($key));
        $this->assertEquals(1, $throttler->getDelay($key, true));
        $throttler->increment($key, -1);
        $this->assertEquals(3, $throttler->getThrottledRequestsCount($key));
    }

    private function waitSecondChanged()
    {
        $second = intval(date("s"));

        while (intval(date("s")) == $second) {
            usleep(50000);
        }
    }
}
