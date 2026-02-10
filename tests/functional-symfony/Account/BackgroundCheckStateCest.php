<?php

namespace Account;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group frontend-functional
 */
class BackgroundCheckStateCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const TECHNICAL = "Background updating is turned off for technical reasons";
    public const TECHNICAL_AWPLUS_ONLY = 'Background updating is turned off for technical reasons for AwardWallet Free members. Please upgrade to AwardWallet Plus to turn it on.';
    public const LOCALLY_PASS = "Background updating is turned off because this account password is stored locally.";

    public function checkWithMailboxOnly(\TestSymfonyGuy $I)
    {
        $login = "bgcheck" . StringUtils::getRandomCode(10);
        $user = $I->createAwUser($login, 'pass', ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $providerId = $I->createAwProvider(null, null, ['State' => PROVIDER_CHECKING_WITH_MAILBOX]);
        $I->createAwAccount($user, $providerId, 'checkWithMailboxOnly');

        $I->amOnPage('/account/list?_switch_user=' . $login);
        $data = json_decode($I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata'));
        $I->assertEquals(
            $data->accounts[0]->BackgroundCheckState,
            $I->grabService(TranslatorInterface::class)->trans('account-mailbox-enable-updating')
        );

        $counter = $I->stubMake(UserMailboxCounter::class, [
            'myOrFamilyMember' => 1,
        ]);
        $I->mockService(UserMailboxCounter::class, $counter);
        $I->amOnPage('/account/list?_switch_user=' . $login);
        $data = json_decode($I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata'));
        $I->assertNull($data->accounts[0]->BackgroundCheckState);
    }

    public function checkProviderCheckingOff(\TestSymfonyGuy $I)
    {
        $login = "bgcheck" . StringUtils::getRandomCode(10);
        $user = $I->createAwUser($login, 'pass', ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $providerId = $I->createAwProvider(null, null, ['State' => PROVIDER_CHECKING_OFF]);
        $I->createAwAccount($user, $providerId, 'checkAwPlusOnly');

        $I->amOnPage('/account/list?_switch_user=' . $login);
        $data = json_decode($I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata'));
        $I->assertEquals($data->accounts[0]->BackgroundCheckState, self::TECHNICAL);
    }

    public function checkPasswordLocally(\TestSymfonyGuy $I)
    {
        $login = "bgcheck" . StringUtils::getRandomCode(10);
        $user = $I->createAwUser($login, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ], true);
        $I->createAwAccount($user, "testprovider", 'checkAwPlusOnly', null, ['SavePassword' => SAVE_PASSWORD_LOCALLY]);

        $I->amOnPage('/account/list?_switch_user=' . $login);
        $data = json_decode($I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata'));
        $I->assertEquals($data->accounts[0]->BackgroundCheckState, self::LOCALLY_PASS);
    }

    public function checkCapitalOneNonUSRegionByApi(\TestSymfonyGuy $I)
    {
        if ($I->grabFromDatabase("Provider", "State", ["ProviderID" => 104]) == PROVIDER_FIXING) {
            $I->markTestSkipped("provider in fixing state");
        }

        $login = "bgcheck" . StringUtils::getRandomCode(10);
        $user = $I->createAwUser($login, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ]);
        $code = $I->grabFromDatabase('Provider', 'Code', ['ProviderID' => 104]); // CapitalOne
        $I->createAwAccount($user, $code, 'checkAwPlusOnly', null, ['SavePassword' => SAVE_PASSWORD_LOCALLY, 'Login2' => 'Canada']);

        $I->amOnPage('/account/list?_switch_user=' . $login);
        $data = json_decode($I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata'));
        $I->assertEquals($data->accounts[0]->BackgroundCheckState, self::LOCALLY_PASS);
    }

    public function checkCapitalOneUSRegionByApi(\TestSymfonyGuy $I)
    {
        $login = "bgcheck" . StringUtils::getRandomCode(10);
        $user = $I->createAwUser($login);
        $code = $I->grabFromDatabase('Provider', 'Code', ['ProviderID' => 104]); // CapitalOne
        $I->createAwAccount($user, $code, 'checkAwPlusOnly', null, ['SavePassword' => SAVE_PASSWORD_LOCALLY, 'Login2' => 'US']);

        $I->amOnPage('/account/list?_switch_user=' . $login);
        $data = json_decode($I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-accountsdata'));
        $I->assertEquals($data->accounts[0]->BackgroundCheckState, null);
    }
}
