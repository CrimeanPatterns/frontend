<?php

namespace AwardWallet\Tests\Unit\Checker;

/**
 * @group frontend-unit
 */
class ZipCodeCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testZipSaving(\CodeGuy $I)
    {
        $propertyId = $I->grabFromDatabase("ProviderProperty", "ProviderPropertyID", ["Code" => "ZipCode", "ProviderID" => null]);
        $I->assertNotEmpty($propertyId);

        $propertyParsedAddressId = $I->grabFromDatabase("ProviderProperty", "ProviderPropertyID", ["Code" => "ParsedAddress", "ProviderID" => null]);
        $I->assertNotEmpty($propertyParsedAddressId);

        $userId = $I->createAwUser(null, null, [], true, true);
        $providerId = $I->createAwProvider(null, null, [], [
            'Parse' => function () {
                /** @var $this \TAccountChecker */
                $this->SetProperty("ZipCode", "22343 1234");
                $this->SetProperty("ParsedAddress", "5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA");
                $this->SetBalance(100);
            },
        ]);
        $accountId = $I->createAwAccount($userId, $providerId, "some");
        $I->checkAccount($accountId);

        $I->seeInDatabase("AccountProperty", ["AccountID" => $accountId, "ProviderPropertyID" => $propertyId, "Val" => "22343 1234"]);
        $I->seeInDatabase("AccountProperty", ["AccountID" => $accountId, "ProviderPropertyID" => $propertyParsedAddressId, "Val" => "5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA"]);
        $user = $I->query("select * from Usr where UserID = $userId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals("22343 1234", $user["Zip"]);
        $I->assertEquals("5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA", $user["ParsedAddress"]);
        $I->assertEquals($providerId, $user["ZipCodeProviderID"]);
        $I->assertEquals($accountId, $user["ZipCodeAccountID"]);
        $I->assertNotEmpty($user["ZipCodeUpdateDate"]);
    }

    public function testBankThenHotel(\CodeGuy $I)
    {
        $propertyId = $I->grabFromDatabase("ProviderProperty", "ProviderPropertyID", ["Code" => "ZipCode", "ProviderID" => null]);
        $I->assertNotEmpty($propertyId);

        $propertyParsedAddressId = $I->grabFromDatabase("ProviderProperty", "ProviderPropertyID", ["Code" => "ParsedAddress", "ProviderID" => null]);
        $I->assertNotEmpty($propertyParsedAddressId);

        $userId = $I->createAwUser(null, null, [], true, true);

        $bankProviderId = $I->createAwProvider(null, null, ['Kind' => PROVIDER_KIND_CREDITCARD], [
            'Parse' => function () {
                /** @var $this \TAccountChecker */
                $this->SetProperty("ZipCode", "BANKZIP");
                $this->SetProperty("ParsedAddress", "5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA");
                $this->SetBalance(100);
            },
        ]);
        $bankAccountId = $I->createAwAccount($userId, $bankProviderId, "some");
        $I->checkAccount($bankAccountId);

        $I->seeInDatabase("AccountProperty", ["AccountID" => $bankAccountId, "ProviderPropertyID" => $propertyId, "Val" => "BANKZIP"]);
        $I->seeInDatabase("AccountProperty", ["AccountID" => $bankAccountId, "ProviderPropertyID" => $propertyParsedAddressId, "Val" => "5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA"]);
        $user = $I->query("select * from Usr where UserID = $userId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals("BANKZIP", $user["Zip"]);
        $I->assertEquals("5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA", $user["ParsedAddress"]);
        $I->assertEquals($bankProviderId, $user["ZipCodeProviderID"]);
        $I->assertEquals($bankAccountId, $user["ZipCodeAccountID"]);
        $I->assertNotEmpty($user["ZipCodeUpdateDate"]);

        $hotelProviderId = $I->createAwProvider(null, null, ['Kind' => PROVIDER_KIND_HOTEL], [
            'Parse' => function () {
                /** @var $this \TAccountChecker */
                $this->SetProperty("ZipCode", "HOTELZIP");
                $this->SetProperty("ParsedAddress", "5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA");
                $this->SetBalance(100);
            },
        ]);
        $hotelAccountId = $I->createAwAccount($userId, $hotelProviderId, "some");
        $I->checkAccount($hotelAccountId);

        $I->seeInDatabase("AccountProperty", ["AccountID" => $bankAccountId, "ProviderPropertyID" => $propertyId, "Val" => "BANKZIP"]);
        $I->seeInDatabase("AccountProperty", ["AccountID" => $bankAccountId, "ProviderPropertyID" => $propertyParsedAddressId, "Val" => "5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA"]);
        $user = $I->query("select * from Usr where UserID = $userId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals("BANKZIP", $user["Zip"]);
        $I->assertEquals("5310 SEIP RD, BETHLEHEM, PA 18017 - 8205, USA", $user["ParsedAddress"]);
        $I->assertEquals($bankProviderId, $user["ZipCodeProviderID"]);
        $I->assertEquals($bankAccountId, $user["ZipCodeAccountID"]);
        $I->assertNotEmpty($user["ZipCodeUpdateDate"]);
    }
}
