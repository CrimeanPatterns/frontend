<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Sitead;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @group frontend-functional
 */
class RegisterCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testReferer(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Referer', 'http://usa-today.com');
        $I->amOnPage('/');
        [$login, $email] = $this->userRegisterSendPost($I);
        $I->assertEquals('http://usa-today.com', $I->grabFromDatabase('Usr', 'Referer', ['Login' => $login]));
    }

    public function testReplacementRefererWithVar2(\TestSymfonyGuy $I): void
    {
        // first init
        $I->haveHttpHeader('Referer', 'http://usa-today.com');
        $I->amOnPage('/?ref=100');
        $refInit = $I->grabService('session')->get(ReferalListener::SESSION_REF_KEY);
        $I->assertEquals($refInit, 100);

        // secondary reinit
        $referer = 'http://google.com';
        $I->haveHttpHeader('Referer', $referer);
        $query = '?var2=exit~test.mid~email';
        $I->amOnPage('/' . $query . '&ref=' . ReferalListener::REPLACEMENT_REF_SITEAD_ID[0]);
        $I->haveHttpHeader('Referer', $referer);

        [$login, $email] = $this->userRegisterSendPost($I);
        $I->assertEquals($referer . $query, $I->grabFromDatabase('Usr', 'Referer', ['Login' => $login]));
    }

    public function testDeleteFromDoNotSendTable(\TestSymfonyGuy $I)
    {
        $email = $I->grabRandomString(8) . '@gmail.com';
        $I->haveInDatabase('DoNotSend', ['Email' => $email]);
        $I->amOnPage('/');
        [$login, $email] = $this->userRegisterSendPost($I, $email);
        $I->dontSeeInDatabase('DoNotSend', ['Email' => $email]);
    }

    public function testSiteAdClickCounter(\TestSymfonyGuy $I)
    {
        $refCode = $I->grabFromDatabase('Usr', 'RefCode', ['UserID' => $I->createAwUser()]);

        $currentCountClicks = $I->grabFromDatabase('SiteAd', 'Clicks', ['SiteAdID' => Sitead::REF_INVITE_OPTION]);
        $I->amOnPage('/?refCode=' . $refCode);

        $newCountClicks = $I->grabFromDatabase('SiteAd', 'Clicks', ['SiteAdID' => Sitead::REF_INVITE_OPTION]);
        $I->assertEquals(1 + $currentCountClicks, $newCountClicks);

        // page refresh does not update counter
        $I->amOnPage('/');
        $I->assertEquals(1 + $currentCountClicks, $newCountClicks);
    }

    public function testSiteAdClickRegisterRef(\TestSymfonyGuy $I)
    {
        $refCode = $I->grabFromDatabase('Usr', 'RefCode', ['UserID' => $I->createAwUser()]);

        $currentCountClicks = $I->grabFromDatabase('SiteAd', 'Clicks', ['SiteAdID' => Sitead::REF_INVITE_OPTION]);
        $I->amOnPage('/?refCode=' . $refCode);

        [$login, $email] = $this->userRegisterSendPost($I);
        $newCountClicks = $I->grabFromDatabase('SiteAd', 'Clicks', ['SiteAdID' => Sitead::REF_INVITE_OPTION]);

        $I->seeInDatabase('Usr', ['Login' => $login, 'CameFrom' => Sitead::REF_INVITE_OPTION]);
        $I->assertEquals(1 + $currentCountClicks, $newCountClicks);
    }

    public function testRegistrationDesktopBrowser(\TestSymfonyGuy $I)
    {
        $I->amOnPage('/');
        [$login, $email] = $this->userRegisterSendPost($I);

        $I->assertEquals(
            Usr::REGISTRATION_PLATFORM_DESKTOP_BROWSER,
            $I->grabFromDatabase('Usr', 'RegistrationPlatform', ['Login' => $login])
        );
        $I->assertEquals(
            Usr::REGISTRATION_METHOD_FORM,
            $I->grabFromDatabase('Usr', 'RegistrationMethod', ['Login' => $login])
        );
    }

    public function listaCCFakeRegistrationBan(\TestSymfonyGuy $I)
    {
        $I->amOnPage('/');
        $I->expectThrowable(ExpectationFailedException::class, function () use ($I) {
            $this->userRegisterSendPost($I, $I->grabRandomString(8) . '@lista.cc');
        });
    }

    public function emailAwDomainRestriction(\TestSymfonyGuy $I)
    {
        $I->amOnPage('/');
        $I->expectThrowable(ExpectationFailedException::class, function () use ($I) {
            $this->userRegisterSendPost($I, sprintf('%s@awardwallet.com', $I->grabRandomString(10)));
        });
        $I->expectThrowable(ExpectationFailedException::class, function () use ($I) {
            $this->userRegisterSendPost($I, sprintf('%s@%s.awardwallet.com', $I->grabRandomString(10), $I->grabRandomString(5)));
        });
    }

    public function testUserAuthStat(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1 Safari/605.1.15');
        $I->amOnPage('/');

        [$login, $email] = $this->userRegisterSendPost($I);
        $userId = $I->grabFromDatabase('Usr', 'UserID', ['Login' => $login]);
        $I->seeInDatabase('UserAuthStat', ['UserID' => $userId, 'Platform' => 'OS X']);
    }

    private function userRegisterSendPost(\TestSymfonyGuy $I, ?string $email = null): array
    {
        if (!empty($email)) {
            $login = substr($email, 0, strpos($email, '@'));
            $email = $email ?? $login . "@gmail.com";
        } else {
            $login = "test" . $I->grabRandomString(8);
            $email = $login . "@gmail.com";
        }

        $I->saveCsrfToken();
        $I->sendPOST(
            "/user/register",
            [
                "user" => [
                    "pass" => "Somepass12",
                    "email" => $email,
                    "firstname" => "John",
                    "lastname" => "Smith",
                ],
                "coupon" => null,
                "recaptcha" => "nomatter",
            ]
        );

        $I->seeResponseCodeIs(200);
        $I->seeInDatabase('Usr', ['Login' => $login]);

        return [$login, $email];
    }
}
