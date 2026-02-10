<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Service\HotelPointValue\SpentAwardsFilter;
use Codeception\Example;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class SpentAwardsFilterCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider patterns
     */
    public function testFilter(\TestSymfonyGuy $I, Example $example): void
    {
        $filter = new SpentAwardsFilter(new NullLogger());
        $result = $filter->filter($example['source']);
        $I->assertEquals($example['result'], $result);
    }

    private function patterns(): array
    {
        return [
            ['source' => '661.82 € + 292,000 points', 'result' => 292000],
            ['source' => '0.01 kr + 70,000 points', 'result' => 70000],
            ['source' => '2,176 ₽ + 10,000 points', 'result' => 10000],
            ['source' => '110.75 € + 0 points', 'result' => 0],
            ['source' => '8000 points Reward', 'result' => 8000],
            ['source' => '8000 pts.', 'result' => 8000],
            ['source' => '35100 pts + 37700 pts', 'result' => 72800],
            ['source' => '30,936 miles', 'result' => 30936],
            ['source' => '1,889 Expedia Rewards points', 'result' => 1889],
            ['source' => '16000 Reward points', 'result' => 16000],
            ['source' => '2000 points Reward', 'result' => 2000],
            ['source' => '18000 pontos Reward', 'result' => 18000],
        ];
    }
}
