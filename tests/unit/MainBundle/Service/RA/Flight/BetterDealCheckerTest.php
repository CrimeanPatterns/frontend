<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Service\RA\Flight\BetterDealChecker;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class BetterDealCheckerTest extends BaseContainerTest
{
    private ?BetterDealChecker $checker;

    public function _before()
    {
        parent::_before();

        $this->checker = $this->container->get(BetterDealChecker::class);
    }

    public function _after()
    {
        $this->checker = null;

        parent::_after();
    }

    /**
     * @dataProvider dealScenarioProvider
     */
    public function testIsBetterDeal(
        ?int $oldMileCost,
        ?int $newMileCost,
        ?float $oldTTTHours,
        ?float $newTTTHours,
        bool $expected
    ): void {
        $this->assertEquals($expected, $this->checker->isBetterDeal($newMileCost, $oldMileCost, $newTTTHours, $oldTTTHours));
    }

    public function dealScenarioProvider(): array
    {
        return [
            'Both MC and TTT qualify - 40% mile savings + 40% time improvement' => [
                50000,
                30000,
                10.0,
                6.0,
                true,
            ],

            'MC qualifies (29% better), TTT improved (14% better)' => [
                70000,
                50000,
                14.0,
                12.0,
                true,
            ],

            'MC qualifies (25% better), TTT same' => [
                60000,
                45000,
                10.0,
                10.0,
                true,
            ],

            'MC qualifies (25% better), TTT slightly worse but within 30min exception' => [
                80000,
                60000,
                9.0,
                9.42, // 9h 25m = 9.42h (25min worse, within 30min exception)
                true,
            ],

            'MC qualifies (22% better), but TTT too much worse (1h, exceeds 30min exception)' => [
                90000,
                70000,
                8.0,
                9.0, // 1h worse, exceeds 30min exception
                false,
            ],

            'MC qualifies (30% better), TTT qualifies via 2.5h override (3h improvement)' => [
                100000,
                70000,
                15.0,
                12.0, // 3h better, qualifies via 2.5h override
                true,
            ],

            'TTT qualifies standard (35% improvement), MC not worse (same cost)' => [
                80000,
                80000, // same cost, not worse
                10.0,
                6.5, // 35% improvement, >30% threshold + >1h difference
                true,
            ],

            'TTT qualifies via 2.5h override (3h improvement), MC not worse (same cost)' => [
                90000,
                90000, // same cost, not worse
                12.0,
                9.0, // 3h improvement, qualifies via 2.5h override
                true,
            ],

            'TTT qualifies standard (30% improvement), but MC is worse (+20k)' => [
                70000,
                90000, // 20k worse
                10.0,
                7.0, // 30% improvement, meets standard threshold
                false,
            ],

            'TTT qualifies via override (3h improvement), but MC is worse (+20k)' => [
                100000,
                120000, // 20k worse
                13.0,
                10.0, // 3h improvement, qualifies via override
                false,
            ],

            'Neither factor qualifies - no MC improvement, TTT improvement too small' => [
                60000,
                60000, // no change
                8.0,
                7.5, // 30min improvement, doesn't meet thresholds
                false,
            ],

            'Neither factor qualifies - no MC improvement, TTT is worse' => [
                70000,
                70000, // no change
                9.0,
                9.5, // 30min worse
                false,
            ],
        ];
    }

    public function testEdgeCases(): void
    {
        $this->assertFalse($this->checker->isBetterDeal(0, 100000, 10.0, 12.0), 'New mile cost is zero');
        $this->assertFalse($this->checker->isBetterDeal(50000, 0, 10.0, 12.0), 'Old mile cost is zero');
        $this->assertFalse($this->checker->isBetterDeal(50000, 100000, 0.0, 12.0), 'New TTT hours is zero');
        $this->assertFalse($this->checker->isBetterDeal(50000, 100000, 10.0, 0.0), 'Old TTT hours is zero');
    }
}
