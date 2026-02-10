<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AccountAccessApi\AuthStateManager;
use AwardWallet\MainBundle\Service\AccountAccessApi\Model\AuthState;
use Codeception\Module\Aw;

/**
 * @group frontend-functional
 * @group mobile
 */
class SharingCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var int
     */
    private $businessUserId;
    /**
     * @var string
     */
    private $refCode;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->businessUserId = $I->createAwUser('sharing1' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD, [
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
            'RefCode' => $this->refCode = StringHandler::getRandomCode(10),
            'BusinessInfo' => [
                'APIEnabled' => true,
                'APIInviteEnabled' => true,
                'APICallbackUrl' => 'http://somecallbackurl.xxx',
            ],
        ]);
    }

    public function businessAdminMustNotShareWithItsBusinessAdmin(\TestSymfonyGuy $I)
    {
        $adminUserId = $I->createStaffUserForBusinessUser($this->businessUserId, UseragentRepository::ACCESS_ADMIN);
        $login = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $adminUserId]);

        $I->sendGET('/m/api/login_status?_switch_user=' . $login);
        $I->sendGET("/m/api/connections/approve/{$this->refCode}/3");
        $I->seeResponseCodeIs(403);
        $I->saveCsrfToken();
        $I->sendGET("/m/api/connections/approve/{$this->refCode}/3");
        $I->seeResponseCodeIs(404);
        $I->sendPOST("/m/api/connections/approve/{$this->refCode}/3");
        $I->seeResponseCodeIs(404);

        $I->seeInDatabase('UserAgent', [
            'ClientID' => $this->businessUserId,
            'AgentID' => $adminUserId,
            'IsApproved' => true,
            'AccessLevel' => UseragentRepository::ACCESS_ADMIN,
        ]);
    }

    public function shareWithWriteAccess(\TestSymfonyGuy $I)
    {
        $access = UseragentRepository::ACCESS_WRITE;
        $userId = $this->createUserAndLogin($I, 'sharing_w' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD);
        $I->sendGET($postUrl = "/m/api/connections/approve/{$this->refCode}/{$access}");
        $I->seeResponseContainsJson([
            'code' => $this->refCode,
            'denyUrl' => 'http://somecallbackurl.xxx?denyAccess=1',
            'accessLevel' => (string) $access,
        ]);
        $I->sendPOST($postUrl);

        $I->seeInDatabase('UserAgent', [
            'ClientID' => $userId,
            'AgentID' => $this->businessUserId,
            'IsApproved' => true,
            'AccessLevel' => $access,
        ]);
    }

    public function shareWithInvalidAuthKey(\TestSymfonyGuy $I)
    {
        $access = UseragentRepository::ACCESS_WRITE;
        $I->updateInDatabase("BusinessInfo", ["APIVersion" => 2], ["UserID" => $this->businessUserId]);
        $this->createUserAndLogin($I, 'sharing_w' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD);
        $I->sendGET($postUrl = "/m/api/connections/approve/{$this->refCode}/{$access}");
        $I->seeResponseCodeIs(404);
    }

    public function shareWithValidAuthKey(\TestSymfonyGuy $I)
    {
        $access = UseragentRepository::ACCESS_WRITE;
        $apiKey = StringUtils::getRandomCode(10);
        $I->updateInDatabase("BusinessInfo", ["APIVersion" => 2, 'APIKey' => $apiKey, 'APIAllowIp' => $I->getClientIp()], ["UserID" => $this->businessUserId]);
        $userId = $this->createUserAndLogin($I, 'sharing_w' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD);
        $authKey = $I->getContainer()->get(AuthStateManager::class)->save(new AuthState(UseragentRepository::ACCESS_WRITE, $this->businessUserId, "123"));
        $I->sendGET($postUrl = "/m/api/connections/approve/{$this->refCode}/{$access}/" . $authKey);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'code' => $this->refCode,
            'denyUrl' => 'http://somecallbackurl.xxx?state=123&denyAccess=1',
            'accessLevel' => (string) $access,
        ]);

        $I->sendPOST($postUrl);

        $I->seeInDatabase('UserAgent', [
            'ClientID' => $userId,
            'AgentID' => $this->businessUserId,
            'IsApproved' => true,
            'AccessLevel' => $access,
        ]);

        $expected = "http://somecallbackurl.xxx?state=123&code=";
        $callbackUlr = $I->grabDataFromResponseByJsonPath(".callbackUrl")[0];
        $I->assertEquals($expected, substr($callbackUlr, 0, strlen($expected)));

        $query = parse_url($callbackUlr, PHP_URL_QUERY);
        parse_str($query, $params);
        $code = $params['code'];
        $I->assertNotEmpty($code);
        $I->assertNotEquals($authKey, $code);

        $I->seeResponseContainsJson([
            'success' => true,
            'callbackUrl' => 'http://somecallbackurl.xxx?state=123&code=' . $code,
        ]);

        $I->amOnBusiness();
        $I->haveHttpHeader("X-Authentication", $apiKey);
        $I->sendGET('/api/export/v1/get-connection-info/' . urlencode($code));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            "userId" => $userId,
        ]);
    }
}
