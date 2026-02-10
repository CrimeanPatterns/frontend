<?php

namespace AwardWallet\Tests\Unit\BusinessTransaction;

use AwardWallet\MainBundle\Entity\BusinessInfo;

/**
 * @group frontend-unit
 * @group billing
 */
class TrialTest extends AbstractTest
{
    /**
     * @dataProvider dataTrialProvider
     */
    public function testTrial($balance, $discount, $trialEndDate, $result)
    {
        $businessInfo = new BusinessInfo($this->business, $balance, $discount, $trialEndDate);
        $this->assertEquals($result, $businessInfo->isTrial());
    }

    public function dataTrialProvider()
    {
        return [
            [0, 0, null, false],
            [0, 0, new \DateTime("-1 day"), false],
            [0, 0, new \DateTime("+1 day"), true],
            [10, 0, new \DateTime("+1 day"), true],
            [-10, 0, new \DateTime("+1 day"), true],
        ];
    }
}
