<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Globals\StringUtils;

/**
 * @group frontend-functional
 */
class ErrorReportingCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const URL = '/test/error_reporting';

    public function testUnauthorized(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, [], false);
        $login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
        $I->sendGET(self::URL, ['_switch_user' => $login]);
        $I->canSeeResponseCodeIs(403);
    }

    public function testAuthorized(\TestSymfonyGuy $I)
    {
        $I->sendGET(self::URL, ['_switch_user' => 'SiteAdmin']);
        $I->seeResponseCodeIs(200);
    }

    public function testReporting(\TestSymfonyGuy $I)
    {
        // remove phpunit error handler, it will interfere with symfony one
        // we will remove two handlers, when executing in group - one handler is not enough
        $oldHandlers = [];

        $oldHandlers[] = set_error_handler(function () {});
        restore_error_handler();
        restore_error_handler();

        $oldHandlers[] = set_error_handler(function () {});
        restore_error_handler();
        restore_error_handler();

        try {
            $I->sendGET(self::URL, ['case' => 'undefinedIndex', '_switch_user' => 'SiteAdmin']);
            $I->assertStringContainsString(\htmlspecialchars('ErrorException: Notice: Undefined index: undefindex'), $I->grabLastMailMessageBody());

            $I->sendGET(self::URL, ['case' => 'undefinedVar', '_switch_user' => 'SiteAdmin']);
            $I->assertStringContainsString(htmlspecialchars('ErrorException: Notice: Undefined variable: undefvar'), $I->grabLastMailMessageBody());

            $I->sendGET(self::URL, ['case' => 'exception', '_switch_user' => 'SiteAdmin']);
            $I->assertStringContainsString(\htmlspecialchars('RuntimeException: Some test exception'), $I->grabLastMailMessageBody());

            $I->sendGET(self::URL, ['case' => 'supressed', '_switch_user' => 'SiteAdmin']);
            $body = $I->grabLastMailMessageBody();
            $I->assertStringNotContainsString("Undefined index: supindex", $body);
            $I->assertStringNotContainsString("Undefined varaible: supvariable", $body);

            $I->sendGET(self::URL, ['case' => 'database', '_switch_user' => 'SiteAdmin', 'random' => $sqlRandom = StringUtils::getRandomCode(10)]);
            $body = $I->grabLastMailMessageBody();
            $I->assertStringContainsString('An exception occurred while executing', $body);
            $I->assertStringContainsString('SomeTable' . $sqlRandom, $body);
            $I->assertStringNotContainsString($sqlRandom . 'Field1Value', $body);
            $I->assertStringNotContainsString($sqlRandom . 'Field2Value', $body);
            $I->assertStringNotContainsString($sqlRandom . 'Field3Value', $body);
            $I->assertStringNotContainsString($sqlRandom . 'Field4Value', $body);

            // test that trace included
            // $I->sendGET(self::URL, ['case' => 'critical-exception', '_switch_user' => 'SiteAdmin']);

            // test console trace
            //            try {
            //                $a = ['a' => 1];
            //                $b = $a['b'];
            //            } catch (\Exception $e) {
            //                $logger = $I->grabService("logger");
            //                $logger->critical('critical-exception', ['contextOne' => 'one', 'exception' => $e]);
            //            }
            //            $body = $I->grabLastMailMessageBody();
        } finally {
            foreach ($oldHandlers as $handler) {
                set_error_handler($handler, E_ALL);
            }
        }
    }

    public function testDieTrace(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader("Authorization", "Basic " . base64_encode("myusername:mypassword"));
        $I->amOnPage("/test/dietrace?_switch_user=SiteAdmin");
        $I->see("We apologize for this inconvenience");
        $body = $I->grabLastMailMessageBody();
        $I->assertStringContainsString('Some trace from controller', $body);
        $I->assertStringContainsString('123321', $body);
        $I->assertStringContainsString('testDieTraceAction', $body);
        $I->assertStringNotContainsString("Basic ", $body);
    }
}
