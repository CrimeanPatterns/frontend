<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Profile;

use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\KeychainReauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthHeaders;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonForm;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use Codeception\Module\Aw;

class KeychainCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use JsonHeaders;
    use JsonForm;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $I->setMobileVersion('4.99.0', MobileVersions::IOS);
    }

    public function testKeychainShouldBePresentedInHeaders(\TestSymfonyGuy $I)
    {
        $this->login($I);
        $this->registerDevice($I);
    }

    public function testKeychainRauthRequiredInChangePassword(\TestSymfonyGuy $I)
    {
        $this->login($I);
        $this->registerDevice($I);
        $I->sendGET(PasswordCest::CHANGE_PASSWORD_ROUTE);
        $I->haveHttpHeader(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);
        $I->sendPUT(PasswordCest::CHANGE_PASSWORD_ROUTE, [
            'pass' => [
                'first' => 'Awdeveloper12',
                'second' => 'Awdeveloper12',
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(["Reauth required"]);
        $I->seeHttpHeader(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);
        $I->seeHttpHeader(MobileReauthHeaders::REQUIRED, 'true');
    }

    public function testKeychainRauthInChangePassword(\TestSymfonyGuy $I)
    {
        $this->login($I);
        $keychainToken = $this->registerDevice($I);
        $I->sendGET(PasswordCest::CHANGE_PASSWORD_ROUTE);
        $I->haveHttpHeader(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);
        $I->haveHttpHeader(MobileReauthHeaders::INPUT, $keychainToken);
        $I->sendPUT(PasswordCest::CHANGE_PASSWORD_ROUTE, [
            'pass' => [
                'first' => 'Awdeveloper12',
                'second' => 'Awdeveloper12',
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function testKeychainRauthInSubsequentChangePasswordAfterChangePassword(\TestSymfonyGuy $I)
    {
        $this->login($I);
        $keychainToken = $this->registerDevice($I);
        $I->sendGET(PasswordCest::CHANGE_PASSWORD_ROUTE);
        $I->haveHttpHeader(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);
        $I->haveHttpHeader(MobileReauthHeaders::INPUT, $keychainToken);
        $I->sendPUT(PasswordCest::CHANGE_PASSWORD_ROUTE, [
            'pass' => [
                'first' => 'Awdeveloper12',
                'second' => 'Awdeveloper12',
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->deleteHeader(MobileReauthHeaders::INPUT);
        $I->sendGET(PasswordCest::CHANGE_PASSWORD_ROUTE);
        $I->haveHttpHeader(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);
        $I->sendPUT(PasswordCest::CHANGE_PASSWORD_ROUTE, [
            'pass' => [
                'first' => 'Awdeveloper12',
                'second' => 'Awdeveloper12',
            ],
            '_token' => $this->grabFormCsrfToken($I),
        ]);
        $I->seeResponseContainsJson(["Reauth required"]);
        $I->seeHttpHeader(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);
        $I->seeHttpHeader(MobileReauthHeaders::REQUIRED, 'true');
    }

    protected function login(\TestSymfonyGuy $I): void
    {
        $I->sendGet('/m/api/login_status');
        $I->sendPost('/m/api/login_check', [
            "_remember_me" => 1,
            'login_password' => [
                'login' => $this->user->getLogin(),
                'pass' => Aw::DEFAULT_PASSWORD,
            ],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    protected function registerDevice(\TestSymfonyGuy $I): string
    {
        $I->sendPost('/m/api/push/register', [
            "type" => MobileVersions::IOS,
            "id" => StringUtils::getRandomCode(20),
        ]);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseJsonMatchesJsonPath('$.deviceId');
        $I->seeHttpHeader(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);

        return $I->grabHttpHeader(MobileReauthHeaders::INPUT);
    }
}
