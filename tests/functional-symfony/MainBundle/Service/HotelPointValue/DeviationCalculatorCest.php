<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Service\HotelPointValue\DeviationCalculator;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use Codeception\Example;

/**
 * @group frontend-functional
 */
class DeviationCalculatorCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProvider
     */
    public function test(\TestSymfonyGuy $I, Example $example)
    {
        $providerId = $I->createAwProvider(null, null, ['Kind' => PROVIDER_KIND_HOTEL]);
        $brandId = $I->haveInDatabase(
            'HotelBrand',
            ['Name' => bin2hex(random_bytes(10)), 'ProviderId' => $providerId, 'Patterns' => 'x']
        );

        foreach ($example['hpvRows'] as $hpvRow) {
            $I->haveInDatabase("HotelPointValue", array_merge(
                [
                    "ProviderID" => $providerId,
                    "BrandId" => $brandId,
                    'HotelName' => 'x',
                    'AlternativeHotelName' => 'x',
                    'AlternativeHotelURL' => 'x',
                    'AlternativeBookingURL' => 'x',
                    'AlternativeCost' => 100,
                    'CheckInDate' => date('Y-m-d', strtotime('+10 day')),
                    'CheckOutDate' => date('Y-m-d', strtotime('+11 day')),
                    'GuestCount' => 1,
                    'KidsCount' => 0,
                    'RoomCount' => 1,
                    'Hash' => 'x',
                    'TotalPointsSpent' => 10000,
                    'TotalTaxesSpent' => 0,
                    'Status' => CalcMileValueCommand::STATUS_GOOD,
                ],
                $hpvRow,
            ));
        }

        /** @var DeviationCalculator $calculator */
        $calculator = $I->grabService(DeviationCalculator::class);
        $result = $calculator->calcDeviationParams($brandId);

        $I->assertEquals($example['deviation'], $result->getDeviation());
        $I->assertEquals($example['average'], $result->getAverage());
        $I->assertEquals($example['basedOn'], $result->getBasedOnRecords());
    }

    public function dataProvider()
    {
        return [
            [
                'hpvRows' => [],
                'deviation' => null,
                'average' => null,
                'basedOn' => 0,
            ],
            [
                'hpvRows' => [
                    ['PointValue' => 100],
                ],
                'deviation' => 0,
                'average' => 100,
                'basedOn' => 1,
            ],
            [
                'hpvRows' => [
                    ['PointValue' => 100],
                    ['PointValue' => 200],
                ],
                'deviation' => 50,
                'average' => 150,
                'basedOn' => 2,
            ],
            [
                'hpvRows' => [
                    ['PointValue' => 100],
                    ['PointValue' => 200, 'CreateDate' => date('Y-m-d', strtotime('-20 month'))],
                ],
                'deviation' => 0,
                'average' => 100,
                'basedOn' => 1,
            ],
            [
                'hpvRows' => [
                    ['PointValue' => 100],
                    ['PointValue' => 200, 'Status' => CalcMileValueCommand::STATUS_REVIEW],
                ],
                'deviation' => 0,
                'average' => 100,
                'basedOn' => 1,
            ],
        ];
    }
}
