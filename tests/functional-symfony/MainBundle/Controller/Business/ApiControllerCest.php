<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Business;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Globals\StringUtils;
use Codeception\Example;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class ApiControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?RouterInterface $router;
    private ?string $apiKey;
    private ?int $businessId;

    public function _before(\TestSymfonyGuy $I)
    {
        $locker = $I->getContainer()->get('aw.security.antibruteforce.api_export');
        $locker->unlock($I->getClientIp());
        $I->amOnBusiness();

        $this->router = $I->getContainer()->get('router');
        $this->apiKey = StringUtils::getRandomCode(20);
        $this->businessId = $I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS, 'BusinessInfo' => ['APIEnabled' => 1, 'APIKey' => $this->apiKey, 'APIAllowIp' => $I->getClientIp(), 'APIVersion' => 2]]);
        $I->haveHttpHeader("X-Authentication", $this->apiKey);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        $this->apiKey = null;
        $this->businessId = null;
    }

    public function testInvalidApiKey(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("X-Authentication", "somebadkey");
        $I->sendPOST($this->router->generate('aw_business_create_auth_url'));
        $I->seeResponseCodeIs(401);
    }

    public function testNoPasswordAccess(\TestSymfonyGuy $I)
    {
        $I->updateInDatabase("BusinessInfo", ["APIEnabled" => 0], ["UserID" => $this->businessId]);
        $I->sendPOST($this->router->generate('aw_business_create_auth_url'));
        $I->seeResponseCodeIs(401);
    }

    public function testBadAccess(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_business_create_auth_url'));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContains("Invalid access");
    }

    public function testBadPlatform(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_business_create_auth_url'), json_encode(["access" => 0, "platform" => "xxx"]));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContains("Invalid platform");
    }

    public function testDesktop(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_business_create_auth_url'), json_encode(["access" => UseragentRepository::ACCESS_READ_BALANCE_AND_STATUS, "platform" => "desktop", "state" => "123"]));
        $I->seeResponseCodeIs(200);
        $refCode = $I->grabFromDatabase("Usr", "RefCode", ["UserID" => $this->businessId]);
        $url = $I->grabDataFromJsonResponse("url");
        $pattern = "#^" . \preg_quote('http://' . $I->grabParameter('host')) . preg_quote("/user/connections/approve?access=1&authKey=") . "(\w+)" . preg_quote("&id=" . urlencode($refCode)) . '$#ims';
        $I->assertEquals(1, preg_match($pattern, $url, $matches));

        $data = $I->getContainer()->get(\Memcached::class)->get("api_auth_state_" . $matches[1]);
        $I->assertEquals(["state" => "123", "access" => UseragentRepository::ACCESS_READ_BALANCE_AND_STATUS, "businessId" => $this->businessId], $data);
    }

    public function testMobile(\TestSymfonyGuy $I)
    {
        $I->sendPOST($this->router->generate('aw_business_create_auth_url'), json_encode(["access" => UseragentRepository::ACCESS_READ_ALL, "platform" => "mobile", "state" => "456"]));
        $I->seeResponseCodeIs(200);
        $refCode = $I->grabFromDatabase("Usr", "RefCode", ["UserID" => $this->businessId]);
        $url = $I->grabDataFromJsonResponse("url");
        $pattern = "#^" . \preg_quote('http://' . $I->grabParameter('host')) . preg_quote("/m/connections/approve/{$refCode}/2/") . '(\w+)$#ims';
        $I->assertEquals(1, preg_match($pattern, $url, $matches));

        $data = $I->getContainer()->get(\Memcached::class)->get("api_auth_state_" . $matches[1]);
        $I->assertEquals(["state" => "456", "access" => UseragentRepository::ACCESS_READ_ALL, "businessId" => $this->businessId], $data);
    }

    public function testApiAccount(\TestSymfonyGuy $I)
    {
        $familyMember = $I->createFamilyMember($this->businessId, 'John', 'Smith');
        $accountId = $this->createAccount($I, $familyMember, $I->createAwProvider());
        $customAccountId = $this->createAccount($I, $familyMember, null, [
            'ProgramName' => 'Test program',
        ]);
        $this->call($I, 'account', $accountId);
        $this->assertJson($I, ['account' => ['accountId' => $accountId]]);
        $this->call($I, 'account', $customAccountId);
        $this->assertJson($I, ['account' => ['accountId' => $customAccountId]]);
    }

    /**
     * @dataProvider americanAirlinesProvider
     */
    public function testApiAAAccount(\TestSymfonyGuy $I, Example $example)
    {
        $familyMember = $I->createFamilyMember($this->businessId, 'John', 'Smith');
        $accountId = $this->createAccount($I, $familyMember, null, [
            'ProgramName' => $example['ProgramName'],
        ]);
        $this->call($I, 'account', $accountId);

        if ($example['Expected']) {
            $this->assertJson($I, ['account' => ['accountId' => $accountId, 'code' => 'aa']]);
        } else {
            $this->assertJson($I, ['account' => ['accountId' => $accountId, 'code' => null]]);
        }
    }

    public function testApiMembers(\TestSymfonyGuy $I)
    {
        $familyMember = $I->createFamilyMember($this->businessId, 'John', 'Smith');
        $accountId = $this->createAccount($I, $familyMember, $I->createAwProvider());
        $customAccountId = $this->createAccount($I, $familyMember, null, [
            'ProgramName' => 'Test program',
        ]);
        $this->call($I, 'member');
        $this->assertJson($I, ['members' => ['accountsIndex' => [
            ['accountId' => $accountId],
            ['accountId' => $customAccountId],
        ]]]);
    }

    public function testApiMember(\TestSymfonyGuy $I)
    {
        $familyMember = $I->createFamilyMember($this->businessId, 'John', 'Smith');
        $accountId = $this->createAccount($I, $familyMember, $I->createAwProvider());
        $customAccountId = $this->createAccount($I, $familyMember, null, [
            'ProgramName' => 'Test program',
        ]);
        $this->call($I, 'member', $familyMember);
        $this->assertJson($I, ['accounts' => [
            ['accountId' => $accountId],
            ['accountId' => $customAccountId],
        ]]);
    }

    /**
     * @dataProvider americanAirlinesProvider
     */
    public function testApiAAMember(\TestSymfonyGuy $I, Example $example)
    {
        $familyMember = $I->createFamilyMember($this->businessId, 'John', 'Smith');
        $accountId = $this->createAccount($I, $familyMember, null, [
            'ProgramName' => $example['ProgramName'],
        ]);
        $this->call($I, 'member', $familyMember);

        if ($example['Expected']) {
            $this->assertJson($I, ['accounts' => ['accountId' => $accountId, 'code' => 'aa']]);
        } else {
            $this->assertJson($I, ['accounts' => ['accountId' => $accountId, 'code' => null]]);
        }
    }

    public function testApiConnectedUsers(\TestSymfonyGuy $I)
    {
        $connectedUser = $I->createAwUser();
        $accountId = $I->createAwAccount($connectedUser, $I->createAwProvider(), 'test-api-' . $I->grabRandomString(5));
        $customAccountId = $I->createAwAccount($connectedUser, null, 'test-api-' . $I->grabRandomString(5), null, [
            'ProgramName' => 'Test program',
        ]);
        $I->shareAwAccount($accountId, $this->businessId);
        $I->shareAwAccount($customAccountId, $this->businessId);
        $I->connectUserWithBusiness($connectedUser, $this->businessId, UseragentRepository::ACCESS_READ_ALL);
        $this->call($I, 'connectedUser');
        $this->logJson($I);
        $this->assertJson($I, ['connectedUsers' => ['accountsIndex' => [
            ['accountId' => $accountId],
            ['accountId' => $customAccountId],
        ]]]);
    }

    public function testApiConnectedUser(\TestSymfonyGuy $I)
    {
        $connectedUser = $I->createAwUser();
        $accountId = $I->createAwAccount($connectedUser, $I->createAwProvider(), 'test-api-' . $I->grabRandomString(5));
        $customAccountId = $I->createAwAccount($connectedUser, null, 'test-api-' . $I->grabRandomString(5), null, [
            'ProgramName' => 'Test program',
        ]);
        $I->shareAwAccount($accountId, $this->businessId);
        $I->shareAwAccount($customAccountId, $this->businessId);
        $I->connectUserWithBusiness($connectedUser, $this->businessId, UseragentRepository::ACCESS_READ_ALL);
        $this->call($I, 'connectedUser', $connectedUser);
        $this->assertJson($I, ['accounts' => [
            ['accountId' => $accountId],
            ['accountId' => $customAccountId],
        ]]);
    }

    /**
     * @dataProvider americanAirlinesProvider
     */
    public function testApiAAConnectedUser(\TestSymfonyGuy $I, Example $example)
    {
        $connectedUser = $I->createAwUser();
        $accountId = $I->createAwAccount($connectedUser, null, 'test-api-' . $I->grabRandomString(5), null, [
            'ProgramName' => $example['ProgramName'],
        ]);
        $I->shareAwAccount($accountId, $this->businessId);
        $I->connectUserWithBusiness($connectedUser, $this->businessId, UseragentRepository::ACCESS_READ_ALL);
        $this->call($I, 'connectedUser', $connectedUser);

        if ($example['Expected']) {
            $this->assertJson($I, ['accounts' => ['accountId' => $accountId, 'code' => 'aa']]);
        } else {
            $this->assertJson($I, ['accounts' => ['accountId' => $accountId, 'code' => null]]);
        }
    }

    private function americanAirlinesProvider(): array
    {
        return [
            ['ProgramName' => 'Test Program', 'Expected' => false],
            ['ProgramName' => 'American Airlines (AAdvantage)', 'Expected' => true],
            ['ProgramName' => 'Test American Airlines (AAdvantage)', 'Expected' => true],
            ['ProgramName' => 'American Airlines', 'Expected' => true],
            ['ProgramName' => 'Test American Airlines', 'Expected' => true],
            ['ProgramName' => 'AAdvantage', 'Expected' => true],
            ['ProgramName' => 'Test AAdvantage', 'Expected' => true],
            ['ProgramName' => 'AA', 'Expected' => true],
            ['ProgramName' => 'Test AA', 'Expected' => false],
            ['ProgramName' => 'American Airlines AAdvantage', 'Expected' => true],
            ['ProgramName' => 'Test American Airlines AAdvantage Test', 'Expected' => true],
            ['ProgramName' => 'AA Advantage', 'Expected' => true],
            ['ProgramName' => 'Test AA Advantage Test', 'Expected' => true],
            ['ProgramName' => 'American', 'Expected' => true],
            ['ProgramName' => 'Test American', 'Expected' => false],
            ['ProgramName' => 'Advantage', 'Expected' => true],
            ['ProgramName' => 'Test Advantage', 'Expected' => false],
            ['ProgramName' => 'American Airlines (AAdvantage) - SAF', 'Expected' => true],
            ['ProgramName' => 'American Airlines (manual updates only)', 'Expected' => true],
        ];
    }

    private function call(\TestSymfonyGuy $I, string $dataset, ?int $id = null)
    {
        if ($id) {
            $url = $this->router->generate('aw_business_api_by_id', [
                'dataset' => $dataset,
                'id' => $id,
            ]);
        } else {
            $url = $this->router->generate('aw_business_api', [
                'dataset' => $dataset,
            ]);
        }

        $I->sendGet($url);
    }

    private function assertJson(\TestSymfonyGuy $I, array $json)
    {
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson($json);
    }

    private function createAccount(\TestSymfonyGuy $I, int $ua, $provider = null, array $fields = []): int
    {
        return $I->createAwAccount($this->businessId, $provider, 'test-api-' . $I->grabRandomString(5), null, array_merge([
            'UserAgentID' => $ua,
        ], $fields));
    }

    private function logJson(\TestSymfonyGuy $I)
    {
        $I->comment(var_export($I->grabDataFromJsonResponse(), true));
    }
}
