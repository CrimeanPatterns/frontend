<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Service\MileValue\MileValueService;

/**
 * @group frontend-unit
 */
class MileValueServiceCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testFetchData(\TestSymfonyGuy $I)
    {
        $ccProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD, "isEarningPotential" => 1]);
        $I->fillAwMileValue($ccProviderId);
        $airlineProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_AIRLINE, "isEarningPotential" => 1]);
        $I->fillAwMileValue($airlineProviderId);
        $I->haveInDatabase("TransferStat", [
            "SourceProviderID" => $ccProviderId,
            "TargetProviderID" => $airlineProviderId,
            "SourceRate" => 1,
            "TargetRate" => 1,
        ]);

        /** @var MileValueService $mileValueService */
        $mileValueService = $I->getContainer()->get(MileValueService::class);

        $data = $mileValueService->getData();
        $I->assertArrayHasKey('transfers', $data);
        $I->assertArrayHasKey('airlines', $data);

        $I->assertArrayHasKey((string) $ccProviderId, $data['transfers']['data']);

        $requiredKeys = ['DisplayName', 'auto', 'show'];

        foreach ($requiredKeys as $key) {
            $I->assertArrayHasKey($key, $data['transfers']['data'][$ccProviderId]);
        }

        $requiredShowKeys = [
            'AvgPointValue',
            'RegionalEconomyMileValue',
            'RegionalBusinessMileValue',
            'GlobalEconomyMileValue',
            'GlobalBusinessMileValue',
        ];

        foreach ($requiredShowKeys as $key) {
            $I->assertArrayHasKey($key, $data['transfers']['data'][$ccProviderId]['show']);
            $I->assertArrayHasKey($key . '_currency', $data['transfers']['data'][$ccProviderId]['show']);
        }
    }
}
