<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AccountAccessApi\AuthStateManager;
use AwardWallet\MainBundle\Service\AccountAccessApi\Model\AuthState;
use Codeception\Module\Aw;

/**
 * @group frontend-functional
 */
class ApproveBusinessConnectionControllerCest
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
    /**
     * @var int
     */
    private $userId;
    /**
     * @var string
     */
    private $login;
    /**
     * @var string
     */
    private $apiKey;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->apiKey = StringUtils::getRandomCode(20);
        $this->businessUserId = $I->createAwUser('sharing1' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD, [
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
            'RefCode' => $this->refCode = StringHandler::getRandomCode(10),
            'BusinessInfo' => [
                'APIEnabled' => true,
                'APIInviteEnabled' => true,
                'APICallbackUrl' => 'http://somecallbackurl.xxx',
                'APIKey' => $this->apiKey,
                'APIAllowIp' => $I->getClientIp(),
                'APIVersion' => 1,
            ],
        ]);
        $this->login = "usr" . StringUtils::getRandomCode(10);
        $this->userId = $I->createAwUser($this->login);
    }

    public function shareWithV1(\TestSymfonyGuy $I)
    {
        $access = UseragentRepository::ACCESS_WRITE;
        $I->amOnPage("/user/connections/approve?id={$this->refCode}&access=$access&_switch_user=" . $this->login);
        $I->seeResponseCodeIs(200);
        $I->see("Account Access Authorization");
        $I->followRedirects(false);
        $I->submitForm('#accept-connection-with-accounts', []);
        $I->seeRedirectTo('http://somecallbackurl.xxx?userId=' . $this->userId);
        $I->seeInDatabase('UserAgent', [
            'ClientID' => $this->userId,
            'AgentID' => $this->businessUserId,
            'IsApproved' => true,
            'AccessLevel' => $access,
        ]);
    }

    public function shareWithV2NoCode(\TestSymfonyGuy $I)
    {
        $access = UseragentRepository::ACCESS_WRITE;
        $I->updateInDatabase("BusinessInfo", ["APIVersion" => 2], ["UserID" => $this->businessUserId]);
        $I->amOnPage("/user/connections/approve?id={$this->refCode}&access=$access&_switch_user=" . $this->login);
        $I->seeResponseCodeIs(404);
        $I->updateInDatabase("BusinessInfo", ["APIVersion" => 1], ["UserID" => $this->businessUserId]);
        $I->amOnPage("/user/connections/approve?id={$this->refCode}&access=$access&_switch_user=" . $this->login);
        $I->seeResponseCodeIs(200);
        $I->updateInDatabase("BusinessInfo", ["APIVersion" => 2], ["UserID" => $this->businessUserId]);
        $I->followRedirects(false);
        $I->submitForm('#accept-connection-with-accounts', []);
        $I->seeResponseCodeIs(404);
        $I->dontSeeInDatabase('UserAgent', [
            'ClientID' => $this->userId,
            'AgentID' => $this->businessUserId,
            'IsApproved' => true,
            'AccessLevel' => $access,
        ]);
    }

    public function shareWithV2ValidCode(\TestSymfonyGuy $I)
    {
        $access = UseragentRepository::ACCESS_WRITE;
        $I->updateInDatabase("BusinessInfo", ["APIVersion" => 2], ["UserID" => $this->businessUserId]);
        $authKey = $I->getContainer()->get(AuthStateManager::class)->save(new AuthState($access, $this->businessUserId, "123"));

        $I->amOnPage("/user/connections/approve?id={$this->refCode}&access=$access&authKey=$authKey&_switch_user=" . $this->login);
        $I->seeResponseCodeIs(200);
        $I->followRedirects(false);
        $I->submitForm('#accept-connection-with-accounts', []);

        $I->seeResponseCodeIs(302);
        $location = $I->grabHttpHeader('Location');
        $expected = "http://somecallbackurl.xxx?state=123&code=";
        $I->assertEquals($expected, substr($location, 0, strlen($expected)));

        $query = parse_url($location, PHP_URL_QUERY);
        parse_str($query, $params);
        $code = $params['code'];
        $I->assertNotEmpty($code);
        $I->assertNotEquals($authKey, $code);

        $I->seeInDatabase('UserAgent', [
            'ClientID' => $this->userId,
            'AgentID' => $this->businessUserId,
            'IsApproved' => true,
            'AccessLevel' => $access,
        ]);

        $I->amOnBusiness();

        $otherApiKey = StringUtils::getRandomCode(20);
        $I->createAwUser('sharing1' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD, [
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
            'RefCode' => $this->refCode = StringHandler::getRandomCode(10),
            'BusinessInfo' => [
                'APIEnabled' => true,
                'APIInviteEnabled' => true,
                'APICallbackUrl' => 'http://somecallbackurl.xxx',
                'APIKey' => $otherApiKey,
                'APIAllowIp' => $I->getClientIp(),
                'APIVersion' => 2,
            ],
        ]);

        $I->haveHttpHeader("X-Authentication", $otherApiKey);
        $I->sendGET('/api/export/v1/get-connection-info/' . urlencode($code));
        $I->seeResponseCodeIs(404);

        $I->haveHttpHeader("X-Authentication", $this->apiKey);
        $I->sendGET('/api/export/v1/get-connection-info/blahblah');
        $I->seeResponseCodeIs(404);

        $I->sendGET('/api/export/v1/get-connection-info/' . urlencode($code));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            "userId" => $this->userId,
        ]);
    }
}
