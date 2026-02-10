<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 */
class FilterBalanceTest extends BaseTest
{
    public function testFloatDot()
    {
        $result = filterBalance("887.31188089561", true);
        $this->assertEquals(887.31, $result);
    }
}
