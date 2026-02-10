<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use Ramsey\Uuid\Uuid;

/**
 * @group frontend-functional
 */
class HistoryControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testHistory(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, ["AccountLevel" => ACCOUNT_LEVEL_AWPLUS], true);
        $login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
        $providerId = $I->createAwProvider(null, null, ["Kind" => PROVIDER_KIND_CREDITCARD, "CanCheckHistory" => 1], [
            'GetHistoryColumns' => function () {
                return [
                    "Type" => "Info",
                    "Eligible Nights" => "Info",
                    "Post Date" => "PostingDate",
                    "Description" => "Description",
                    "Starpoints" => "Miles",
                    "Bonus" => "Bonus",
                ];
            },
        ]);
        $accountId = $I->createAwAccount($userId, $providerId, "test");
        $subAccountId = $I->createAwSubAccount($accountId);
        $date = time();
        $I->haveInDatabase("AccountHistory", [
            "UUID" => Uuid::uuid4()->toString(),
            "AccountID" => $accountId,
            "SubAccountID" => $subAccountId,
            "PostingDate" => date("Y", $date) . "-01-28 00:00:00",
            "Description" => "Existing tx 1",
            "Miles" => 22,
            "Position" => 1,
            'Amount' => 17.37,
            'Multiplier' => 1.2,
        ]);
        $I->haveInDatabase("AccountHistory", [
            "UUID" => Uuid::uuid4()->toString(),
            "AccountID" => $accountId,
            "SubAccountID" => $subAccountId,
            "PostingDate" => date("Y", $date) . "-01-29 00:00:00",
            "Description" => "Existing tx 1",
            "Miles" => 125,
            "Position" => 1,
            'Amount' => 100,
            'Multiplier' => 1.2,
        ]);
        $I->haveInDatabase("AccountHistory", [
            "UUID" => Uuid::uuid4()->toString(),
            "AccountID" => $accountId,
            "SubAccountID" => $subAccountId,
            "PostingDate" => date("Y", $date) . "-01-30 00:00:00",
            "Description" => "Existing tx 2",
            "Miles" => 29,
            "Position" => 1,
            'Amount' => 23.22,
            'Multiplier' => 1.3,
        ]);

        $I->amOnPage("/account/history/{$accountId}/{$subAccountId}?_switch_user={$login}");
        $I->seeResponseCodeIsSuccessful();

        if (!preg_match('#/\* DATA START \*/(.+)/\* DATA END \*/#ims', $I->grabResponse(), $matches)) {
            $I->fail("can't see data");
        }

        $data = json_decode($matches[1], true);
        codecept_debug($data);
        $I->assertCount(3, $data['historyRows']);

        $miles = $data['historyRows'][0]['cells'][5];
        $I->assertArrayContainsArray([
            "field" => "Miles",
            "value" => 29,
            'column' => 'Starpoints',
            'valueType' => 'decimal',
            'type' => 'string',
            'multiplier' => 1.25,
            'color' => null,
            'valueFormatted' => '+29',
        ], $miles);

        $miles = $data['historyRows'][1]['cells'][5];
        $I->assertArrayContainsArray([
            "field" => "Miles",
            "value" => 125,
            'column' => 'Starpoints',
            'valueType' => 'decimal',
            'type' => 'string',
            'multiplier' => 1.25,
            'color' => null,
            'valueFormatted' => '+125',
        ], $miles);

        $miles = $data['historyRows'][2]['cells'][5];
        $I->assertArrayContainsArray([
            "field" => "Miles",
            "value" => 22,
            'column' => 'Starpoints',
            'valueType' => 'decimal',
            'type' => 'string',
            'multiplier' => 1.25,
            'color' => null,
            'valueFormatted' => '+22',
        ], $miles);
    }
}
