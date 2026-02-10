<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\AccountHistory\MultiplierService;
use Codeception\Example;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Service\AccountHistory\MultiplierService
 */
class MultiplierServiceCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider roundingDataProvider
     */
    public function testRounding(\TestSymfonyGuy $I, Example $example)
    {
        $result = MultiplierService::calculate($example['amount'], $example['miles'], $example['providerId']);
        $I->assertEquals($example['result'], (string) $result);
    }

    private function roundingDataProvider()
    {
        return [
            ['providerId' => Provider::CAPITAL_ONE_ID, 'amount' => 182.97, 'miles' => 229, 'result' => '1'],
            ['providerId' => Provider::CAPITAL_ONE_ID, 'amount' => 200, 'miles' => 300, 'result' => '2'],
            ['providerId' => Provider::CAPITAL_ONE_ID, 'amount' => 200, 'miles' => 305, 'result' => '2'],
        ];
    }
}
