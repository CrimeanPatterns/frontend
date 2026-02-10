<?php

namespace AwardWallet\Tests\Unit\Checker;

class GoogleStateCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testGoogleCookies(\CodeGuy $I)
    {
        $userId = $I->createAwUser(null, null, [], true, true);
        $accountId = $I->createAwAccount($userId, "testprovider", "Checker.GoogleStateSet");

        $options = new \AuditorOptions();
        $options->checkStrategy = \CommonCheckAccountFactory::STRATEGY_CHECK_LOCAL;
        $cookies = null;

        $options->onBrowserReady = function (\TAccountChecker $checker) use (&$cookies) {
            if ($checker->http->driver instanceof \SeleniumDriver) {
                /** @var \SeleniumDriver $driver */
                $driver = $checker->http->driver;

                if (!empty($cookies)) {
                    $driver->setCookies($cookies);
                }
            }
        };

        $options->onComplete = function (\TAccountChecker $checker) use (&$user, &$cookies) {
            if ($checker->http->driver instanceof \SeleniumDriver) {
                /** @var \SeleniumDriver $driver */
                $driver = $checker->http->driver;
                $cookies = $driver->getCookies(["https://google.com"]);
            }
        };

        \CommonCheckAccountFactory::checkAndSave($accountId, $options);
        $I->assertEquals(ACCOUNT_CHECKED, $I->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));
        $NID = $I->grabFromDatabase("AccountProperty", "Val", ["AccountID" => $accountId]);
        $I->assertNotEmpty($NID);

        $accountId = $I->createAwAccount($userId, "testprovider", "Checker.GoogleStateGet");
        \CommonCheckAccountFactory::checkAndSave($accountId, $options);
        $I->assertEquals(ACCOUNT_CHECKED, $I->grabFromDatabase("Account", "ErrorCode", ["AccountID" => $accountId]));
        $I->assertEquals($NID, $I->grabFromDatabase("AccountProperty", "Val", ["AccountID" => $accountId]));
    }
}
