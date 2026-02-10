<?php

namespace AwardWallet\tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringEndsWith;

/**
 * @group mobile
 * @group frontend-functional
 */
class DisabledClientPasswordAccessCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var int */
    protected $providerId;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'dcpa-', 'userpass-', [], true);

        $this->providerId = $this->accountSteps->createAwProvider(null, null, [
            'MobileAutoLogin' => MOBILE_AUTOLOGIN_DESKTOP_EXTENSION,
            'AutoLogin' => AUTOLOGIN_EXTENSION,
            'LoginURL' => $loginURL = 'https://awardwallet.some.site',
            'State' => PROVIDER_CHECKING_EXTENSION_ONLY,
            'PasswordRequired' => true,
        ]);
    }

    public function noAutologinAndUpdateForAccountWithDCPA(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, $this->providerId, null, null, ['DisableClientPasswordAccess' => 1]);
        $this->accountSteps->loadData();
        $I->seeResponseContainsJson(['accounts' => ["a{$accountId}" => [
            'Autologin' => [
                'mobileExtension' => false,
                'desktopExtension' => false,
            ],
            'Access' => [
                'update' => false,
            ],
        ]]]);
        assertStringEndsWith("/account/redirect?ID={$accountId}&fromApp=1", $I->grabDataFromJsonResponse("accounts.a{$accountId}.Autologin.loginUrl"));
    }

    public function userWithOldAppCanNotDisableClientPasswordAccess(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.24.0+100500');
        $accountId = $I->createAwAccount($this->userId, $this->providerId, null, null, ['DisableClientPasswordAccess' => 1]);
        $this->accountSteps->loadAccountForm(AccountSteps::getUrl('edit', $accountId));
        $I->dontSeeResponseJsonMatchesJsonPath('$..[?(@.name = "disableclientpasswordaccess")]');
    }

    public function userWithUpdatedAppCanDisableClientPasswordAccess(\TestSymfonyGuy $I)
    {
        $this->doCheckEditForm($I, 0, 1, false, true);
    }

    public function userWithUpdatedAppCanNotSimplyDisableClientPasswordAccess(\TestSymfonyGuy $I)
    {
        $this->doCheckEditForm($I, 1, 1, true, false);
    }

    protected function doCheckEditForm(\TestSymfonyGuy $I, int $initialDbValue, int $expectedFinalDbValue, bool $formExpectedValue, bool $formSubmitValue)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.24.14+100500');
        $accountId = $I->createAwAccount($this->userId, $this->providerId, null, $password = 'PASS', ['DisableClientPasswordAccess' => $initialDbValue]);
        $formFields = $this->accountSteps->loadAccountForm(AccountSteps::getUrl('edit', $accountId));
        $field = $I->grabDataFromResponseByJsonPath('$..[?(@.name = "disableclientpasswordaccess")]')[0];
        assertEquals(true, $field['submitData']);
        assertEquals(true, $field['mapped']);
        assertEquals($formExpectedValue, $field['value']);
        $formFields['disableclientpasswordaccess'] = $formSubmitValue;
        $formFields['pass'] = $password;
        $I->sendPUT(AccountSteps::getUrl('edit', $accountId), $formFields);
        $I->seeResponseContainsJson([
            'needUpdate' => false,
            'account' => [
                'ID' => $accountId,
            ],
        ]);
        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'DisableClientPasswordAccess' => $expectedFinalDbValue]);
    }
}
