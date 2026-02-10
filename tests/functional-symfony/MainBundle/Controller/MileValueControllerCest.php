<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\ProviderMileValue;
use Codeception\Example;

/**
 * @group frontend-functional
 * @coversDefaultClass \AwardWallet\MainBundle\Controller\MileValueController
 */
class MileValueControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider enabledDataProvider
     */
    public function testEnabled(\TestSymfonyGuy $I, Example $example)
    {
        $airlineProviderId = $I->createAwProvider(null, null, [
            "Kind" => PROVIDER_KIND_AIRLINE,
            "isEarningPotential" => 1,
        ]);

        // $I->fillMileValueData([$airlineProviderId, 2, 7, 84, 86, 92, 96, 179, 184, 416]);
        $I->fillAwMileValue($airlineProviderId, ["Status" => $example['enabled'] ? ProviderMileValue::STATUS_ENABLED : ProviderMileValue::STATUS_IN_PROGRESS]);
        $providerName = $I->grabFromDatabase("Provider", "DisplayName", ["ProviderID" => $airlineProviderId]);

        $data = $this->getMileValueData($I);

        $I->assertArrayContainsArray([
            "transfers" => [],
            "airlines" => [],
            "hotels" => [],
            "banks" => [],
        ], $data);

        if ($example['expectedVisibility']) {
            $I->seeInSource($providerName);
            $I->seeInSource($airlineProviderId);
        } else {
            $I->dontSeeInSource($providerName);
            $I->dontSeeInSource($airlineProviderId);
        }
    }

    public function testTransferNoRates(\TestSymfonyGuy $I)
    {
        $creditCardProviderId = $I->createAwProvider(null, null, [
            "Kind" => PROVIDER_KIND_CREDITCARD,
            "isEarningPotential" => 1,
        ]);
        $I->fillMileValueData([$creditCardProviderId, 2, 7, 84, 86, 92, 96, 179, 184, 416]);
        $I->fillAwMileValue($creditCardProviderId, [], 0);
        $providerName = $I->grabFromDatabase("Provider", "DisplayName", ["ProviderID" => $creditCardProviderId]);

        $data = $this->getMileValueData($I);

        $I->assertArrayContainsArray([
            "transfers" => [],
            "airlines" => [],
            "hotels" => [],
            "banks" => [],
        ], $data);

        $I->dontSeeInSource($providerName);
    }

    public function testTransferWithRates(\TestSymfonyGuy $I)
    {
        $creditCardProviderId = $I->createAwProvider(null, null, [
            "Kind" => PROVIDER_KIND_CREDITCARD,
            "isEarningPotential" => 1,
        ]);
        // MileValue records should be ignored, we do not include them for transfers
        // https://redmine.awardwallet.com/issues/21324#note-2
        // results that are marked as "GOOD" or "NEW" from this program "Chase Ultimate Rewards" currently should not be factored into the average here
        $I->fillAwMileValue($creditCardProviderId);
        $I->createAwMileValue($creditCardProviderId, ["AlternativeCost" => 100000]);
        $providerName = $I->grabFromDatabase("Provider", "DisplayName", ["ProviderID" => $creditCardProviderId]);

        $airlineProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_AIRLINE, "isEarningPotential" => 1]);
        $I->fillAwMileValue($airlineProviderId);
        $I->haveInDatabase("TransferStat", [
            "SourceProviderID" => $creditCardProviderId,
            "TargetProviderID" => $airlineProviderId,
            "SourceRate" => 1,
            "TargetRate" => 1,
        ]);

        $data = $this->getMileValueData($I);

        $I->assertArrayContainsArray([
            "transfers" => [],
            "airlines" => [],
            "hotels" => [],
            "banks" => [],
        ], $data);

        $I->seeInSource($providerName);
        $I->seeInSource($creditCardProviderId);

        $mileValues = array_values(array_filter($data['transfers']['data'], fn (array $row) => $row['ProviderID'] === $creditCardProviderId))[0]['show'];
        $I->assertArrayContainsArray([
            'AvgPointValue' => 0.9,
            'AvgPointValue_count' => 20,
            'AvgPointValue_sumSpent' => 200000,
            'AvgPointValue_currency' => '¢',
            'RegionalEconomyMileValue' => 0.9,
            'RegionalEconomyMileValue_count' => 5,
            'RegionalEconomyMileValue_sumSpent' => 50000,
            'RegionalEconomyMileValue_currency' => '¢',
            'RegionalBusinessMileValue' => 0.9,
            'RegionalBusinessMileValue_count' => 5,
            'RegionalBusinessMileValue_sumSpent' => 50000,
            'RegionalBusinessMileValue_currency' => '¢',
            'GlobalEconomyMileValue' => 0.9,
            'GlobalEconomyMileValue_count' => 5,
            'GlobalEconomyMileValue_sumSpent' => 50000,
            'GlobalEconomyMileValue_currency' => '¢',
            'GlobalBusinessMileValue' => 0.9,
            'GlobalBusinessMileValue_count' => 5,
            'GlobalBusinessMileValue_sumSpent' => 50000,
            'GlobalBusinessMileValue_currency' => '¢',
        ], $mileValues);
    }

    public function testTransferRatesButNoMileValue(\TestSymfonyGuy $I)
    {
        $creditCardProviderId = $I->createAwProvider(null, null, [
            "Kind" => PROVIDER_KIND_CREDITCARD,
            "isEarningPotential" => 1,
        ]);
        $I->fillMileValueData([$creditCardProviderId, 2, 7, 84, 86, 92, 96, 179, 184, 416]);
        $I->fillAwMileValue($creditCardProviderId, [], 0);
        $providerName = $I->grabFromDatabase("Provider", "DisplayName", ["ProviderID" => $creditCardProviderId]);

        $airlineProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_AIRLINE, "isEarningPotential" => 1]);
        $I->haveInDatabase("TransferStat", [
            "SourceProviderID" => $creditCardProviderId,
            "TargetProviderID" => $airlineProviderId,
            "SourceRate" => 1,
            "TargetRate" => 1,
        ]);

        $data = $this->getMileValueData($I);

        $I->assertArrayContainsArray([
            "transfers" => [],
            "airlines" => [],
            "hotels" => [],
            "banks" => [],
        ], $data);

        $I->dontSeeInSource($providerName);
        // too gready, matches random numbers $I->dontSeeInSource($creditCardProviderId);
    }

    private function enabledDataProvider()
    {
        return [
            ['enabled' => true, 'expectedVisibility' => true],
            ['enabled' => false, 'expectedVisibility' => false],
        ];
    }

    private function getMileValueData(\TestSymfonyGuy $I): array
    {
        $I->amOnPage("/point-mile-values");
        $I->seeResponseCodeIsSuccessful();

        if (!preg_match('#/\* DATA START \*/(.+)/\* DATA END \*/#ims', $I->grabResponse(), $matches)) {
            $I->fail("can't see data");
        }

        return json_decode($matches[1], true);
    }
}
