<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Outbox;
use AwardWallet\MainBundle\Security\Authenticator\Step\Recaptcha\CaptchaStepHelper;
use AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;

/**
 * @group frontend-functional
 */
class LoginCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    public const V6_IP = '0:0:0:0:0:ffff:9455:881f'; // ipv6
    public const V6_UK = '2a03:b0c0:1:d0::611:4001';

    public function testUserWithIpv6(\TestSymfonyGuy $I)
    {
        $user = $this->createUser($I, ['RegistrationIP' => self::V6_IP, 'LastLogonIP' => self::V6_IP, "InBeta" => 1, "BetaApproved" => 1]);
        $I->seeInDatabase("Usr", ["UserID" => $user['userId'], "RegistrationIP" => self::V6_IP, "LastLogonIP" => self::V6_IP]);

        $I->wantToTest("login with ipv6 address");
        $I->haveServerParameter("REMOTE_ADDR", self::V6_IP);
        $this->loginUser($user, $I);
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeCookie("AuthKey", "/login_check");
        $I->seeCookie("AuthKey", "/m/api/login_check");
        $I->seeInDatabase("Usr", ["UserID" => $user['userId'], "LastLogonIP" => self::V6_IP]);
        $userIpID = $I->grabFromDatabase("UserIP", "UserIPID", ["UserID" => $user['userId'], "IP" => self::V6_IP]);
        $useIpOutboxId = $I
            ->query('
                select OutboxID 
                from Outbox 
                where 
                    TypeID = ' . Outbox::TYPE_USERIP_POINT . '
                    and Payload->"$.UserIPID" = ' . $userIpID . '
                    and JSON_VALUE(Payload, "$.Lat") is not null
                    and JSON_VALUE(Payload, "$.Lng") is not null',
            )
            ->fetchColumn();
        $I->assertNotFalse($useIpOutboxId, 'UserIP point not found');
        $usrLastPointId = $I
            ->query('
                select OutboxID 
                from Outbox 
                where 
                    TypeID = ' . Outbox::TYPE_USR_LAST_LOGON_POINT . '
                    and Payload->"$.UserID" = ' . $user['userId'] . '
                    and JSON_VALUE(Payload, "$.Lat") is not null
                    and JSON_VALUE(Payload, "$.Lng") is not null',
            )
            ->fetchColumn();
        $I->assertNotFalse($usrLastPointId, 'UsrLastLogonPoint not found');
        $I->assertEquals(Country::UNITED_STATES, $I->grabFromDatabase("Usr", "CountryID", ["UserID" => $user['userId']]));
    }

    public function testUserWithUnknownIp(\TestSymfonyGuy $I)
    {
        $unknownIp = '192.168.100.1';
        $user = $this->createUser($I, ['RegistrationIP' => $unknownIp, 'LastLogonIP' => $unknownIp]);
        $I->seeInDatabase("Usr", ["UserID" => $user['userId'], "RegistrationIP" => $unknownIp, "LastLogonIP" => $unknownIp]);
        $I->wantToTest("login with unknown address");
        $I->haveServerParameter("REMOTE_ADDR", $unknownIp);
        $this->loginUser($user, $I);
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeCookie("AuthKey", "/login_check");
        $I->seeCookie("AuthKey", "/m/api/login_check");
        $I->seeInDatabase("Usr", ["UserID" => $user['userId'], "LastLogonIP" => $unknownIp]);
        $userIpID = $I->grabFromDatabase("UserIP", "UserIPID", ["UserID" => $user['userId'], "IP" => $unknownIp]);
        $useIpOutboxId = $I
            ->query('
                select OutboxID 
                from Outbox 
                where 
                    TypeID = ' . Outbox::TYPE_USERIP_POINT . '
                    and Payload->"$.UserIPID" = ' . $userIpID . '
                    and JSON_VALUE(Payload, "$.Lat") is null
                    and JSON_VALUE(Payload, "$.Lng") is null',
            )
            ->fetchColumn();
        $I->assertNotFalse($useIpOutboxId, 'UserIP point not found');
        $usrLastPointId = $I
            ->query('
                select OutboxID 
                from Outbox 
                where 
                    TypeID = ' . Outbox::TYPE_USR_LAST_LOGON_POINT . '
                    and Payload->"$.UserID" = ' . $user['userId'] . '
                    and JSON_VALUE(Payload, "$.Lat") is null
                    and JSON_VALUE(Payload, "$.Lng") is null',
            )
            ->fetchColumn();
        $I->assertNotFalse($usrLastPointId, 'UsrLastLogonPoint not found');
        $I->assertNull($I->grabFromDatabase("Usr", "CountryID", ["UserID" => $user['userId']]));
    }

    public function testLoginFromUk(\TestSymfonyGuy $I)
    {
        $user = $this->createUser($I, ['RegistrationIP' => self::V6_UK, 'LastLogonIP' => self::V6_UK]);

        $I->haveServerParameter("REMOTE_ADDR", self::V6_UK);
        $this->loginUser($user, $I);
        $I->seeResponseContainsJson(["success" => true]);
        $I->assertEquals(Country::UK, $I->grabFromDatabase("Usr", "CountryID", ["UserID" => $user['userId']]));
    }

    public function testSessionIdChangedOnLogin(\TestSymfonyGuy $I)
    {
        $user = $this->createUser($I, ['RegistrationIP' => self::V6_UK, 'LastLogonIP' => self::V6_UK]);

        $I->haveServerParameter("REMOTE_ADDR", self::V6_UK);
        $this->loadCSRF($I);
        $anonymousSessionId = $I->grabCookie("MOCKSESSID");
        $I->assertNotEmpty($anonymousSessionId);
        $I->sendPOST("/user/check", []);
        $I->assertEquals($anonymousSessionId, $I->grabCookie("MOCKSESSID"));
        $clientCheck = $I->grabService('session')->get('client_check');
        $I->haveHttpHeader("X-Scripted", $clientCheck['result']);
        $params = ["login" => $user['login'], "password" => $user['password'], "_csrf_token" => $I->grabDataFromJsonResponse("csrf_token")];
        $I->sendPOST("/login_check", $params);
        $I->seeResponseContainsJson(["success" => true]);
        $authorizedSessionId = $I->grabCookie("MOCKSESSID");
        $I->assertNotEmpty($authorizedSessionId);
        $I->assertNotEquals($anonymousSessionId, $authorizedSessionId);
    }

    public function testRecaptchaHeader(\TestSymfonyGuy $I)
    {
        $user = $this->createUser($I);

        $I->haveHttpHeader(CaptchaStepHelper::WANT_RECAPTCHA_HEADER, "true");
        $this->loginUser($user, $I);
        $I->seeResponseContainsJson(["success" => false, "recaptchaRequired" => true]);
    }

    public function testUserWithoutPasswordShouldNotBeAuthorized(\TestSymfonyGuy $I)
    {
        $user = $this->createUser($I);
        $user['password'] = null;
        $I->updateInDatabase('Usr', ['Pass' => null], ['UserID' => $user['userId']]);
        $this->loginUser($user, $I);
        $I->seeResponseContainsJson(["success" => false]);
    }
}
