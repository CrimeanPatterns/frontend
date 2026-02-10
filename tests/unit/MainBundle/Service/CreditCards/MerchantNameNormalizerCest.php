<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Service\CreditCards\MerchantNameNormalizer;
use Codeception\Example;

/**
 * @group frontend-unit
 */
class MerchantNameNormalizerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProvider
     */
    public function testNormalize(\TestSymfonyGuy $I, Example $example)
    {
        $normalizer = new MerchantNameNormalizer();
        $I->assertEquals($example['output'], $normalizer->normalize($example['input']));
    }

    private function dataProvider()
    {
        return [
            ['input' => 'Two  spaces', 'output' => 'TWO SPACES'],
            ['input' => 'Space # notword', 'output' => 'SPACE NOTWORD'],
            ['input' => ' # trim', 'output' => 'TRIM'],
        ];
    }
}
