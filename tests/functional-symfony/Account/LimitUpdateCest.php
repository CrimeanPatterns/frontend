<?php

namespace Account;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\ThrottlerCounter;
use AwardWallet\MainBundle\Updater\Event\LimitEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class LimitUpdateCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    private ?RouterInterface $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
    }

    public function testRegularUserUpdateLimit(\TestSymfonyGuy $I)
    {
        $data = $this->init($I);

        for ($i = 0; $i <= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            $I->sendPOST($this->router->generate('aw_account_updater_start'),
                $this->enrichStartKey($data['_updaterPost']));
            $I->canSeeResponseIsJson();

            ThrottlerCounter::DEFAULT_LIMIT_SUCCESS === $i
                ? (function () use ($I) {
                    $event = $I->grabDataFromResponseByJsonPath("events[?(@.type=='fail')]")[0];
                    $I->assertEquals(LimitEvent::ERROR_CODE_LOCKOUT, $event['code']);
                })()
                : $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
            $this->wait();
        }
    }

    public function testUserAwPlusUpdate(\TestSymfonyGuy $I)
    {
        $data = $this->init($I, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);

        for ($i = 0; $i <= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            $I->sendPOST($this->router->generate('aw_account_updater_start'),
                $this->enrichStartKey($data['_updaterPost']));
            $I->canSeeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
            $this->wait();
        }
    }

    public function testUpdateAllowOneError(\TestSymfonyGuy $I)
    {
        $data = $this->init($I, [], 'provider.error');
        $I->sendPOST($this->router->generate('aw_account_updater_start'), $this->enrichStartKey($data['_updaterPost']));
        $I->canSeeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='error')]");
        $this->wait();

        $I->updateInDatabase('Account', ['Login' => 'balance.point'], ['AccountID' => $data['accountId']]);

        for ($i = 0; $i <= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            $I->sendPOST($this->router->generate('aw_account_updater_start'),
                $this->enrichStartKey($data['_updaterPost']));
            $I->canSeeResponseIsJson();

            $i >= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS
                ? $I->canSeeResponseJsonMatchesJsonPath("events[?(@.code==" . LimitEvent::ERROR_CODE_LOCKOUT . ")]")
                : $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
            $this->wait();
        }
    }

    public function testUpdateLimitDisabledUntil(\TestSymfonyGuy $I)
    {
        $data = $this->init($I, [], 'balance.point', ['UpdateLimitDisabledUntil' => date('Y-m-d', time() + 86400)]);
        $I->assertNotNull($I->grabFromDatabase('Account',
            'UpdateLimitDisabledUntil',
            ['AccountID' => $data['accountId']]));

        for ($i = 0; $i <= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            $I->sendPOST($this->router->generate('aw_account_updater_start'),
                $this->enrichStartKey($data['_updaterPost']));
            $I->canSeeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
            $this->wait();
        }
    }

    public function testUnlimitUpdateForImpersonate(\TestSymfonyGuy $I)
    {
        $data = $this->init($I);

        $userId = $I->createAwUser(null, null, [], true);
        $accountId = $I->createAwAccount($userId, 'testprovider', 'balance.point');
        $I->amOnPage($this->router->generate('aw_manager_impersonate', ['UserID' => $userId]));
        $I->seeResponseCodeIs(200);
        $I->seeInField('form[UserID]', $userId);
        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase('ImpersonateLog', [
            'UserID' => $data['userId'],
            'TargetUserID' => $userId,
            'IPAddress' => '127.0.0.1',
            'UserAgent' => 'Symfony BrowserKit',
        ]);

        $I->amOnPage('/manager/');
        $I->see('You are impersonated');

        $I->amOnPage($this->router->generate('aw_account_list'));

        for ($i = 0; $i <= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            $I->sendPOST($this->router->generate('aw_account_updater_start'), json_encode([
                'accounts' => [$accountId],
                'startKey' => StringHandler::getRandomString(ord('a'), ord('z'), 10),
                'source' => 'one',
                'extensionAvailable' => false,
                'supportedBrowser' => true,
            ]));
            $I->seeResponseIsJson();
            $I->canSeeResponseJsonMatchesJsonPath("events[?(@.type=='updated')]");
        }
    }

    public function throttlerCounterLimit(\TestSymfonyGuy $I)
    {
        $throttlerCounter = $I->grabService(ThrottlerCounter::class);
        $key = 'test_throttler_' . mt_rand();

        for ($i = 0; $i <= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            $i === ThrottlerCounter::DEFAULT_LIMIT_SUCCESS
                ? $I->assertTrue($throttlerCounter->throttle($key))
                : $I->assertFalse($throttlerCounter->throttle($key));

            $throttlerCounter->success($key);
            $this->waitThrottlerTest();
        }

        $throttlerCounter->clear($key);
    }

    public function throttlerCounterAllowFail(\TestSymfonyGuy $I)
    {
        $throttlerCounter = $I->grabService(ThrottlerCounter::class);
        $key = 'test_throttler_' . mt_rand();

        $throttlerCounter->failure($key);
        $this->waitThrottlerTest();

        for ($i = 0; $i <= ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            $i === ThrottlerCounter::DEFAULT_LIMIT_SUCCESS
                ? $I->assertTrue($throttlerCounter->throttle($key))
                : $I->assertFalse($throttlerCounter->throttle($key));

            $throttlerCounter->success($key);
            $this->waitThrottlerTest();
        }

        $throttlerCounter->clear($key);
    }

    public function throttlerCounterNotAllowFail(\TestSymfonyGuy $I)
    {
        $throttlerCounter = new ThrottlerCounter($I->grabService(\Memcached::class),
            3600,
            ThrottlerCounter::DEFAULT_LIMIT_SUCCESS,
            0
        );
        $key = 'test_throttler_' . mt_rand();

        $throttlerCounter->failure($key);
        $this->waitThrottlerTest();

        for ($i = 0; $i < ThrottlerCounter::DEFAULT_LIMIT_SUCCESS; $i++) {
            1 + $i === ThrottlerCounter::DEFAULT_LIMIT_SUCCESS
                ? $I->assertTrue($throttlerCounter->throttle($key))
                : $I->assertFalse($throttlerCounter->throttle($key));

            $throttlerCounter->success($key);
            $this->waitThrottlerTest();
        }

        $throttlerCounter->clear($key);
    }

    public function testConditions(\TestSymfonyGuy $I)
    {
        $conditions = [
            ['success' => 2],
            ['success' => 1, 'failure' => 5],
            ['failure' => 10],
        ];

        /** @var ThrottlerCounter $throttlerCounter */
        $throttlerCounter = $I->grabService(ThrottlerCounter::class);
        $throttlerCounter->setConditions($conditions);
        $key = 'test_throttler_' . mt_rand();

        // ['failure' => 10],
        $I->assertFalse($throttlerCounter->throttle($key));

        for ($i = 0; $i <= $conditions[2]['failure']; $i++) {
            $throttlerCounter->failure($key);
            $this->waitThrottlerTest();
        }
        $I->assertTrue($throttlerCounter->throttle($key));

        // ['success' => 1, 'failure' => 5],
        $key = 'test_throttler_' . mt_rand();
        $I->assertFalse($throttlerCounter->throttle($key));

        for ($i = 0; $i <= $conditions[1]['failure']; $i++) {
            $throttlerCounter->failure($key);
            $this->waitThrottlerTest();
        }
        $I->assertFalse($throttlerCounter->throttle($key));
        $throttlerCounter->success($key);
        $this->waitThrottlerTest();
        $I->assertTrue($throttlerCounter->throttle($key));

        // ['success' => 2],
        $key = 'test_throttler_' . mt_rand();
        $throttlerCounter->success($key);
        $this->waitThrottlerTest();
        $throttlerCounter->success($key);
        $this->waitThrottlerTest();
        $I->assertTrue($throttlerCounter->throttle($key));
    }

    private function enrichStartKey(string $data): string
    {
        $data = \json_decode($data, true);
        $data['startKey'] = \random_int(1, 1000 * 1000) + 1000 * 1000;

        return \json_encode($data);
    }

    private function init(\TestSymfonyGuy $I, $userFields = [], $accountLogin = 'balance.point', $accountFields = [])
    {
        $data = [];
        $data['userId'] = $I->createAwUser(null, null, $userFields, true);
        $I->haveInDatabase('GroupUserLink', ['SiteGroupID' => 49, 'UserID' => $data['userId']]);
        $data['userLogin'] = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $data['userId']]);
        $data['accountId'] = $I->createAwAccount($data['userId'], 'testprovider', $accountLogin, null, $accountFields);
        $data['_updaterPost'] = json_encode([
            'accounts' => [$data['accountId']],
            'source' => 'one',
            'extensionAvailable' => false,
            'supportedBrowser' => true,
        ]);

        $I->amOnPage($this->router->generate('aw_account_list', ['_switch_user' => $data['userLogin']]));
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/json');

        return $data;
    }

    private function wait()
    {
        // usleep(25000);
    }

    private function waitThrottlerTest()
    {
        usleep(25000);
    }
}
