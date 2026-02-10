<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\ProviderMileValue;
use Ramsey\Uuid\Uuid;

/**
 * @group frontend-functional
 */
class TransactionAnalyzerControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testTransactions(\TestSymfonyGuy $I)
    {
        [$userId, $providerId, $accountId, $creditCardId, $merchantId, $subAccountId, $uuid, $date] = $this->createBaseData($I);

        $I->haveInDatabase("ProviderMileValue", [
            "ProviderID" => $providerId,
            "Status" => ProviderMileValue::STATUS_ENABLED,
            "AvgPointValue" => 0.03,
        ]);

        $data = $this->loadTransactionsPage($I, $userId);

        $I->assertCount(1, $data['transactions']);
        $I->assertArrayContainsArray(
            [
                "uuid" => $uuid,
                "date" => null,
                "description" => "Existing tx",
                "cardName" => sprintf('SubAccount %d', $creditCardId),
                "category" => "",
                "amount" => 100,
                "miles" => 1000,
                "currency" => null,
                "multiplier" => 10,
                "pointsValue" => 0.3,
                "subAccountId" => $subAccountId,
                'formatted' => [
                    'date' => '1/1/' . date("y"),
                    'miles' => '+1,000',
                    'amount' => '$100',
                    'pointsValue' => '$0.30',
                    'minValue' => '$0.00',
                    'maxValue' => '$0.00',
                    'potentialMinValue' => '$0',
                    'potentialMaxValue' => '$0',
                ],
            ],
            $data['transactions'][0]
        );
        $I->assertArrayHasKey("potentialPointsValue", $data['transactions'][0]);
        $I->assertArrayHasKey("potentialPointsValueFormatted", $data['transactions'][0]);
    }

    /* tmp disabled
    public function testCobrandCard(\TestSymfonyGuy $I)
    {
        [$userId, $providerId, $accountId, $creditCardId, $merchantId, $subAccountId, $uuid, $date] = $this->createBaseData($I);
        $cobrandProviderId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_AIRLINE]);
        $I->updateInDatabase("CreditCard", ["CobrandProviderID" => $cobrandProviderId], ["CreditCardID" => $creditCardId]);
        // we should consider disabled PMV records, but to not show them on /point-mile-values pages (tested in MileValueControllerCest)
        // $I->fillAwMileValue($cobrandProviderId, ["Status" => ProviderMileValue::STATUS_ENABLED]);
        $I->fillMileValueData([$cobrandProviderId], true);

        $data = $this->loadTransactionsPage($I, $userId);

        $I->assertCount(1, $data['transactions']);
        $I->assertArrayContainsArray(
            [
                "uuid" => $uuid,
                "date" => null,
                "description" => "Existing tx",
                "cardName" => sprintf('SubAccount %d', $creditCardId),
                "category" => "",
                "amount" => 100,
                "miles" => 1000,
                "milesFormatted" => "+1,000",
                "currency" => null,
                "multiplier" => 10,
                "pointsValue" => null,
                "subAccountId" => $subAccountId,
                'formatted' => [
                    'date' => '1/1/' . date("y"),
                    'miles' => '+1,000',
                    'amount' => '$100',
                    'pointsValue' => '$0',
                    'pointsValueCut' => '$0',
                    'minValue' => '$0.00',
                    'maxValue' => '$0.00',
                    'cashEquivalent' => '',
                    'potentialPointsValue' => '$2.34',
                    'potentialMinValue' => '$0',
                    'potentialMaxValue' => '$0',
                ],
                'dateFormatted' => null,
                'amountFormatted' => null,
                'potentialMiles' => 300,
                'potentialMilesFormatted' => '+300',
                'pointsValueFormatted' => null,
                'minValue' => null,
                'maxValue' => null,
                'potentialMinValue' => 0,
                'potentialMaxValue' => 0,
                'pointName' => null,
                'cashEquivalent' => 0,
                'isProfit' => false,
                'diffCashEq' => '',
                'potential' => null,
                'potentialColor' => 'yellow',
                'potentialPointsValue' => 2.34,
                'potentialPointsValueFormatted' => '$2.34',
            ],
            $data['transactions'][0]
        );
    }
    */

    private function loadTransactionsPage(\TestSymfonyGuy $I, int $userId): array
    {
        $I->switchToUser($userId);
        $I->followRedirects(false);
        $I->amOnPage("/transactions/");
        $I->seeResponseCodeIsSuccessful();

        if (!preg_match('#/\* DATA START \*/(.+)/\* DATA END \*/#ims', $I->grabResponse(), $matches)) {
            $I->fail("can't see data");
        }

        return $this->fixJsonParse($matches[1]);
    }

    private function createBaseData(\TestSymfonyGuy $I): array
    {
        $userId = $I->createAwUser();
        $providerId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD, "isEarningPotential" => 1]);
        $accountId = $I->createAwAccount($userId, $providerId, "test");
        $creditCardId = $I->createAwCreditCard($providerId);
        $merchantId = $I->createAwMerchant();
        $subAccountId = $I->createAwSubAccount($accountId, [
            'CreditCardID' => $creditCardId,
            'DisplayName' => sprintf('SubAccount %d', $creditCardId),
        ]);

        $uuid = Uuid::uuid4()->toString();
        $date = time();
        $I->haveInDatabase("AccountHistory", [
            "UUID" => $uuid,
            "AccountID" => $accountId,
            "SubAccountID" => $subAccountId,
            "MerchantID" => $merchantId,
            "PostingDate" => date("Y", $date) . "-01-01 00:00:00",
            "Description" => "Existing tx",
            "Miles" => 1000,
            "Position" => 1,
            'Amount' => 100,
        ]);

        return [$userId, $providerId, $accountId, $creditCardId, $merchantId, $subAccountId, $uuid, $date];
    }

    private function fixJsonParse(string $jsonString): array
    {
        $json = trim($jsonString, '{}, ');
        $json = str_replace(["\r", "\n"], '', $json);
        $json = preg_replace('/\s+/', ' ', $json);
        $json = trim($json, '{}, ');

        $data = ('{' . trim($json) . '}');

        return json_decode($data, true);
    }
}
