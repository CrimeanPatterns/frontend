<?php

namespace AwardWallet\tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;

use function PHPUnit\Framework\assertEquals;

/**
 * @group mobile
 * @group frontend-functional
 */
class OAuthCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'accounts-', 'userpass-', [], true);
    }

    public function oauthForm(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.12.0');
        // add
        $form = $this->accountSteps->loadAccountForm(AccountSteps::getUrl('add', 104));
        $form['notrelated'] = true;
        $form['authInfo'] = $oauthCode = 'some_random_code';

        $I->sendPOST(AccountSteps::getUrl('add', 104), $form);
        $accountId = $I->grabDataFromJsonResponse('account.ID');

        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'AuthInfo' => 'some/random/code']);

        // edit
        $I->sendGET(AccountSteps::getUrl('edit', $accountId));
        $form = $this->accountSteps->loadAccountForm(AccountSteps::getUrl('edit', $accountId));
        assertEquals('some/random/code', $form['authInfo']);
        $form['authInfo'] = $oauthCode = 'some_new_random_code';
        $I->sendPUT(AccountSteps::getUrl('edit', $accountId), $form);
        $I->grabDataFromJsonResponse('account.ID');

        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'AuthInfo' => 'some/new/random/code']);
    }

    public function oauthFormNotRequired(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.13.0');
        // add
        $form = $this->accountSteps->loadAccountForm(AccountSteps::getUrl('add', 104));
        $form['notrelated'] = true;
        $form['login2'] = 'CA';
        $form['login'] = 'login';
        $form['pass'] = 'pass';

        $I->sendPOST(AccountSteps::getUrl('add', 104), $form);
        $accountId = $I->grabDataFromJsonResponse('account.ID');

        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'AuthInfo' => null]);
    }

    public function oauthOldVersions(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.11.0');

        // add
        $form = $this->accountSteps->loadAccountForm(AccountSteps::getUrl('add', 104));
        $I->assertNotContains('authInfo', array_keys($form));
        $form['login'] = 'test';
        $form['login2'] = 'CA';
        $form['pass'] = 'Pass';
        $form['notrelated'] = true;

        $I->sendPOST(AccountSteps::getUrl('add', 104), $form);
        $data = $I->grabDataFromJsonResponse('');
        $accountId = $data['account']['ID'];

        // edit
        $form = $this->accountSteps->loadAccountForm(AccountSteps::getUrl('edit', $accountId));
        $I->assertNotContains('authInfo', array_keys($form));
        $form['login'] = 'test1';
        $form['login2'] = 'CA';
        $form['pass'] = 'Pass';
        $form['notrelated'] = true;
        $accountId = $data['account']['ID'];
    }
}
