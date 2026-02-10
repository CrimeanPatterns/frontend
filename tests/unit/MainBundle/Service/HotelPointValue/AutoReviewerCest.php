<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\HotelBrand;
use AwardWallet\MainBundle\Entity\HotelPointValue;
use AwardWallet\MainBundle\Service\HotelPointValue\AutoReviewer;
use AwardWallet\MainBundle\Service\HotelPointValue\DeviationCalculator;
use AwardWallet\MainBundle\Service\HotelPointValue\DeviationCalculatorResult;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use Codeception\Example;

/**
 * @group frontend-unit
 */
class AutoReviewerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProvider
     */
    public function test(\TestSymfonyGuy $I, Example $example): void
    {
        $deviationCalculator = $I->stubMakeEmpty(DeviationCalculator::class, [
            'calcDeviationParams' => $example['deviationResult'],
        ]);

        $hpv = new HotelPointValue();
        $hpv->setPointValue($example['pointValue']);
        $hpv->setStatus($example['status'] ?? CalcMileValueCommand::STATUS_NEW);

        if (!($example['noBrand'] ?? false)) {
            $brand = new HotelBrand();
            $brand->setId(random_int(1000000, 2000000));
            $brand->setName("Shimano");
            $hpv->setBrand($brand);
        }

        /** @var AutoReviewer $reviewer */
        $reviewer = $I->createInstance(AutoReviewer::class, ['deviationCalculator' => $deviationCalculator]);
        $result = $reviewer->check($hpv);

        $I->assertEquals($example['expectedResult'], $result);
    }

    public function dataProvider()
    {
        return [
            [
                'pointValue' => 100,
                'deviationResult' => new DeviationCalculatorResult(null, null, 0),
                'expectedResult' => null,
            ],
            [
                'pointValue' => 130,
                'deviationResult' => new DeviationCalculatorResult(50, 100, 1),
                'expectedResult' => null,
            ],
            [
                'pointValue' => 180,
                'deviationResult' => new DeviationCalculatorResult(50, 100, 1),
                'expectedResult' => "deviation 50,  average: 100, delta: 80, based on: 1 records, brand: Shimano",
            ],
            [
                'pointValue' => 180,
                'deviationResult' => new DeviationCalculatorResult(50, 100, 1),
                'noBrand' => true,
                'expectedResult' => null,
            ],
            [
                'pointValue' => 180,
                'deviationResult' => new DeviationCalculatorResult(50, 100, 1),
                'status' => CalcMileValueCommand::STATUS_REVIEW,
                'expectedResult' => null,
            ],
        ];
    }
}
