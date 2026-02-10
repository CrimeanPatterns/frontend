<?php

namespace AwardWallet\Tests\FunctionalSymfony\Business;

use AwardWallet\MainBundle\Controller\Business\ApiController;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class AccountApiCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        $locker = $I->getContainer()->get('aw.security.antibruteforce.api_export');
        $locker->unlock($I->getClientIp());
        $I->amOnBusiness();
    }

    public function testNoToken(\TestSymfonyGuy $I)
    {
        $I->sendGET('/api/export/v1/account');
        $I->seeResponseCodeIs(403);
    }

    public function testInvalidToken(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("X-Authentication", "some");
        $I->sendGET('/api/export/v1/account');
        $I->seeResponseCodeIs(401);
    }

    public function testDisabledAPI(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS, 'BusinessInfo' => ['APIEnabled' => 0, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp()]]);
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $I->sendGET('/api/export/v1/account');
        $I->seeResponseCodeIs(401);
    }

    public function testNoPasswordAccess(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $accountId = $this->createAccount($I, ACCESS_READ_ALL, ['APIEnabled' => 1, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp()]);
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $I->sendGET('/api/export/v1/account/' . $accountId);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["account" => ["accountId" => $accountId]]);
        $I->dontSeeDataInJsonResponse("account.password");
    }

    public function testPasswordAccessNoIP(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $accountId = $this->createAccount($I, ACCESS_WRITE, ['APIEnabled' => 1, 'APIKey' => $apiKey]);
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $I->sendGET('/api/export/v1/account/' . $accountId);
        $I->seeResponseCodeIs(401);
        $I->seeResponseContainsJson(["error" => "Access Denied as your IP is not white-listed"]);
        $I->dontSeeDataInJsonResponse("account.password");
    }

    public function testPasswordAccessNoKey(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $accountId = $this->createAccount($I, ACCESS_WRITE, ['APIEnabled' => 1, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp()]);
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $I->sendGET('/api/export/v1/account/' . $accountId);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["account" => ["accountId" => $accountId]]);
        $I->dontSeeDataInJsonResponse("account.password");
    }

    public function testPasswordAccessGranted(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $keyPair = openssl_pkey_new(['private_key_bits' => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keyPair, $privateKey);
        $keyDetails = openssl_pkey_get_details($keyPair);
        $publicKey = $keyDetails["key"];
        $accountId = $this->createAccount($I, ACCESS_WRITE, ['APIEnabled' => 1, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp(), 'PublicKey' => $publicKey]);
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $I->sendGET('/api/export/v1/account/' . $accountId);
        $I->seeResponseCodeIs(200);
        $cryptedPassword = $I->grabDataFromJsonResponse("account.password");
        $I->assertNotEmpty($cryptedPassword);
        openssl_private_decrypt(base64_decode($cryptedPassword), $decrypted, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
        $I->assertEquals('123', $decrypted);
    }

    public function testAmericanSharing(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $keyPair = openssl_pkey_new(['private_key_bits' => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keyPair, $privateKey);
        $keyDetails = openssl_pkey_get_details($keyPair);
        $publicKey = $keyDetails["key"];
        $accountId = $this->createAccount($I, ACCESS_WRITE, ['APIEnabled' => 1, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp(), 'PublicKey' => $publicKey], 'aa');
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $I->sendGET('/api/export/v1/account/' . $accountId);
        $I->seeResponseCodeIs(404);

        //        foreach (ApiController::RESTRICTED_AA_FIELDS as $field) {
        //            if ($field === 'lastDetectedChange') {
        //                continue;
        //            }
        //            $restrictedData = $I->grabDataFromJsonResponse(sprintf("account.%s", $field));
        //            $I->assertEquals(ApiController::RESTRICTED_MSG, $restrictedData);
        //        }
    }

    public function testMembersAccountIndex(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $businessId = $I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS, 'BusinessInfo' => ['APIEnabled' => 1, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp()]], true);
        $memberId = $I->createFamilyMember($businessId, "John", "Smith");
        $accountId = $I->createAwAccount($businessId, "testprovider", "balance.random", null, ["UserAgentID" => $memberId]);

        $I->sendGET('/api/export/v1/member');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            "members" => [
                [
                    "memberId" => $memberId,
                    "fullName" => "John Smith",
                    "email" => '',
                    "accountsIndex" => [
                        [
                            "accountId" => $accountId,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testProvidersList(\TestSymfonyGuy $I)
    {
        $apiKey = StringUtils::getRandomCode(20);
        $keyPair = openssl_pkey_new(['private_key_bits' => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keyPair, $privateKey);
        $keyDetails = openssl_pkey_get_details($keyPair);
        $publicKey = $keyDetails["key"];
        $accountId = $this->createAccount($I, ACCESS_WRITE, ['APIEnabled' => 1, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp(), 'PublicKey' => $publicKey, 'APIAllowIp' => $I->getClientIp()]);
        $I->haveHttpHeader("X-Authentication", $apiKey);

        $testProvider = [
            'code' => 'testprovider',
            'displayName' => 'Aegean Airlines (Miles &amp; Bonus)',
        ];
        $providersList = [$testProvider];
        $loyaltyCommunicatorMock = $I->stubMake(ApiCommunicator::class, [
            'getProvidersList' => Stub::exactly(1, function (bool $isAwDefaultUser) use ($providersList, $I) {
                $I->assertEquals(false, $isAwDefaultUser);

                return json_encode($providersList);
            }),
            'getProviderInfo' => Stub::exactly(1, function ($id, bool $isAwDefaultUser) use ($testProvider, $I) {
                $I->assertEquals(false, $isAwDefaultUser);

                return json_encode($testProvider);
            }),
        ]);
        $I->mockService(ApiCommunicator::class, $loyaltyCommunicatorMock);

        $I->sendGET('/api/export/v1/providers/list');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson($providersList);
        $I->sendGET('/api/export/v1/providers/30');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson($testProvider);
    }

    private function createAccount(\TestSymfonyGuy $I, $accessLevel, $businessInfo, $provider = 'testprovider')
    {
        $businessId = $I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS, 'BusinessInfo' => $businessInfo], true);
        $userId = $I->createAwUser(null, null, [], true);
        $accountId = $I->createAwAccount($userId, $provider, "balance.random", '123');
        $connectionId = $I->createConnection($userId, $businessId, true, true, ['AccessLevel' => $accessLevel]);
        $I->createConnection($businessId, $userId, true, true, ['AccessLevel' => ACCESS_NONE]);
        $I->shareAwAccountByConnection($accountId, $connectionId);

        return $accountId;
    }
}
