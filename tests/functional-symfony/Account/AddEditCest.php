<?php

namespace Account;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Globals\StringUtils;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class AddEditCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const PROVIDER = 636; // Test provider
    private $username;
    private $userId;

    /**
     * @var Router
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true, true);
        $this->username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $this->userId]);
        $this->router = $I->grabService('router');
    }

    public function checkAddAccountAsUser(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['providerId' => self::PROVIDER]) . "?_switch_user=" . $this->username);
        $I->see('Test Provider');
        $I->canSeeInSource('// testprovider form.js injected');
        $I->selectOption('Login', '1.subaccount: 1 subaccount');
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);
        $I->seeInDatabase('Account', ['UserID' => $this->userId, 'ProviderID' => self::PROVIDER]);
    }

    public function checkAddCustomAccountAsUser(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add') . "?_switch_user=" . $this->username);
        $I->see('Manually track award program');
        $I->selectOption('Type', 'Hotels');
        $I->fillField('Program Name', 'Test');
        $I->fillField('Account', 'Test');
        $I->fillField('Balance', '12,345.6');
        $I->submitForm('#account-form', []);
        $I->seeInDatabase('Account', ['UserID' => $this->userId, 'ProviderID' => null]);
        $balance = $I->grabFromDatabase('Account', 'Balance', ['UserID' => $this->userId, 'ProviderID' => null]);
        $I->assertEqualsWithDelta((float) '12345.6', $balance, 0.000000001);
    }

    public function checkAddCouponAsUser(\TestSymfonyGuy $I)
    {
        $couponNumber = StringUtils::getPseudoRandomString(10);
        $I->createAwProvider($providerName = 'testme' . StringUtils::getPseudoRandomString(5), $providerName, ['Kind' => PROVIDER_KIND_AIRLINE]);

        $I->amOnPage($this->router->generate('aw_coupon_add') . "?_switch_user=" . $this->username);
        $I->see('Manually track travel voucher or gift card');
        $I->selectOption('Category', PROVIDER_KIND_AIRLINE);
        $I->fillField('Company', $providerName);
        $I->fillField('Note', 'Test');
        $I->fillField('Cert / Card / Voucher #', $couponNumber);
        $I->fillField('Type', 'couponText');
        $I->submitForm('.main-form', []);
        $I->seeInDatabase('ProviderCoupon', ['UserID' => $this->userId, 'CardNumber' => $couponNumber]);
    }

    public function checkAddCustomAccountToMember(\TestSymfonyGuy $I)
    {
        $fm = $I->createFamilyMember($this->userId, 'Boo', 'Boom');
        $I->amOnPage($this->router->generate('aw_account_add', ['_switch_user' => $this->username, 'agentId' => $fm]));
        $I->see('Manually track award program');
        $I->selectOption('Type', 'Hotels');
        $I->fillField('Program Name', 'Test');
        $I->fillField('Account', 'Test');
        $I->fillField('Balance', '10');
        $I->submitForm('#account-form', []);
        $I->seeInDatabase('Account', ['UserID' => $this->userId, 'UserAgentID' => $fm, 'ProviderID' => null]);
    }

    public function checkAddCustomAccountToConnected(\TestSymfonyGuy $I)
    {
        $u2 = $I->createAwUser();
        $reverseAgentId = $I->createConnection($this->userId, $u2, true, true, ['AccessLevel' => ACCESS_WRITE]);
        $agentId = $I->createConnection($u2, $this->userId, true, true, ['AccessLevel' => ACCESS_WRITE]);
        $I->amOnPage($this->router->generate('aw_account_add', ['_switch_user' => $this->username, 'agentId' => $agentId]));
        $I->see('Manually track award program');
        $I->selectOption('Type', 'Hotels');
        $I->fillField('Program Name', 'Test');
        $I->fillField('Account', 'Test');
        $I->fillField('Balance', '10');
        $I->followRedirects(false);
        $I->submitForm('#account-form', []);
        $location = $I->grabHttpHeader('Location');

        if (!preg_match('#\d+$#', $location, $matches)) {
            $I->fail("could not see redirect to account");
        }
        $accountId = $matches[0];
        $account = $I->query("select * from Account where AccountID = $accountId")->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals($u2, $account['UserID']);
        $I->assertNull($account['UserAgentID']);
        $I->seeInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $agentId]);
        $I->dontSeeInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $reverseAgentId]);
    }

    public function checkAddCustomAccountToForeignMember(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['_switch_user' => $this->username, 'agentId' => 7465])); // Paulina Vereschaga
        $I->seeResponseCodeIs(403);
    }

    public function passwordTrimSpecialSymbol(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['providerId' => 155]) . '?_switch_user=' . $this->username);
        $I->see('S7 Priority');

        for ($sym = '', $i = 0; $i < 8; $i++) {
            $sym .= \chr(random_int(1, 32));
        }
        $clearPassword = 'password'; // $clearPassword = 'pass' . $sym . 'word';  ## not worked with $this->errorSymbolAsciiCodeLess32()
        $dirtyPassword = $sym . $clearPassword . $sym;

        $I->fillField('#account_login', '1234567890');
        $I->fillField('#account_pass', $dirtyPassword);
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);

        $I->seeInDatabase('Account', ['UserID' => $this->userId, 'ProviderID' => 155]);
        $encodedPassword = $I->grabFromDatabase('Account', 'Pass', ['UserID' => $this->userId]);
        /** @var PasswordDecryptor $decryptor */
        $decryptor = $I->grabService(PasswordDecryptor::class);
        $I->assertEquals($clearPassword, $decryptor->decrypt($encodedPassword));
    }

    public function dontAllowSymbolAsciiCodeLess32(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['providerId' => 155, '_switch_user' => $this->username]));
        $I->see('S7 Priority');

        $badPassword = 'bad  symbol';

        $I->fillField('#account_login', '1234567890');
        $I->fillField('#account_pass', $badPassword);
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);

        $translator = $I->grabService('translator');
        $I->see($translator->trans('invalid.symbols', [], 'validators'), '.row-pass');
    }

    public function allowSimpleSpace(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['providerId' => 155]) . '?_switch_user=' . $this->username);
        $I->see('S7 Priority');

        $password = 'pass word';

        $I->fillField('#account_login', '1234567890');
        $I->fillField('#account_pass', $password);
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);

        $I->seeInDatabase('Account', ['UserID' => $this->userId, 'ProviderID' => 155]);
    }

    public function dontAllowSymbolUnicodeEmpty(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['providerId' => 155, '_switch_user' => $this->username]));
        $I->see('S7 Priority');

        $badPassword = 'bad   symbol'; // <-- unicode non visible symbol

        $I->fillField('#account_login', '1234567890');
        $I->fillField('#account_pass', $badPassword);
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);

        $translator = $I->grabService('translator');
        $I->see($translator->trans('invalid.symbols', [], 'validators'), '.row-pass');
    }
}
