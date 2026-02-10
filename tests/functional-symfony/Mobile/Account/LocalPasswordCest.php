<?php

namespace AwardWallet\tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;

/**
 * @group mobile
 * @group frontend-functional
 */
class LocalPasswordCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'accounts-', 'userpass-', [], true);
    }

    public function checkLocalPasswordsProviderCanNotCheck(\TestSymfonyGuy $I)
    {
        $providerId = $I->createAwProvider('testprovider' . StringHandler::getRandomCode(10), 'testprovid' . StringHandler::getRandomCode(10), ['CanCheck' => 0]);
        $this->checkLocalPasswords($I, $providerId, false);
    }

    public function checkLocalPasswordsProviderCanCheck(\TestSymfonyGuy $I)
    {
        $accountSteps = $this->accountSteps;
        $providerId = $I->createAwProvider('testprovider' . StringHandler::getRandomCode(10), 'testprovid' . StringHandler::getRandomCode(10));
        $this->checkLocalPasswords($I, $providerId, true);

        $I->wantTo('add account, with local password initially');
        $accountId = $accountSteps->addAccount($providerId, 'localpassword', 'localpassword', ['savepassword' => SAVE_PASSWORD_LOCALLY]);

        $I->wantTo('edit account, change login only, missing local password');
        $accountSteps->deleteLocalPasswords();
        $accountSteps->editAccount($accountId, 'newloginlalala', ['', null], [], [
            ['formData', self::TRANS_MISSING_LOCAL_PASSWORD],
        ]);

        $I->wantTo('edit account, change login only, missing local password');
        $accountSteps->deleteLocalPasswords();
        $accountSteps->editAccount($accountId, 'newloginlalala', ['', ''], [], [
            ['formData.children.2', self::TRANS_VALUE_BLANK],
        ]);

        $I->wantTo('edit account, provide password');
        $accountSteps->editAccount($accountId, null, ['', 'somepass']);
        $I->seeResponseContainsJson(['needUpdate' => true]);
    }

    /**
     * @param int $providerId
     * @param bool $canCheck
     */
    protected function checkLocalPasswords(\TestSymfonyGuy $I, $providerId, $canCheck)
    {
        $accountSteps = $this->accountSteps;
        $accountId = (int) $accountSteps->addAccount($providerId, 'testlogin', 'testpassword');

        $I->wantTo('edit account, save password locally without changing or providing it, just move from database to cookies');
        $accountSteps->editAccount($accountId, null, ['testpassword', null], ['savepassword' => SAVE_PASSWORD_LOCALLY]);
        $I->seeResponseContainsJson(['needUpdate' => false]);

        $familyMemberId = $this->userSteps->addFamilyMember($this->userId, 'Randle_' . StringHandler::getRandomCode(10), 'McMurphy');
        $I->wantTo('Change account owner');
        $accountSteps->editAccount($accountId, null, ['', null], ['owner' => $this->userId . '_' . $familyMemberId]);
        $I->seeResponseContainsJson(['needUpdate' => false]);

        $I->wantTo('edit account, change local password');
        $accountSteps->editAccount($accountId, null, ['testpassword', 'passwordtest11222longlong']);
        $I->seeResponseContainsJson(['needUpdate' => $canCheck]);

        $I->wantTo('edit account, leave password intact, change login only');
        $accountSteps->editAccount($accountId, 'testnewlogin2', ['passwordtest11222longlong', null]);
        $I->seeResponseContainsJson(['needUpdate' => $canCheck]);

        $I->wantTo('edit account, move password from local to database');
        $accountSteps->editAccount($accountId, null, ['passwordtest11222longlong', null], ['savepassword' => SAVE_PASSWORD_DATABASE]);
        $I->seeResponseContainsJson(['needUpdate' => false]);

        $I->wantTo('edit account, move password from database to local');
        $accountSteps->editAccount($accountId, null, ['passwordtest11222longlong', null], ['savepassword' => SAVE_PASSWORD_LOCALLY]);
        $I->seeResponseContainsJson(['needUpdate' => false]);

        $I->wantTo('edit account, nove password from local to database with password modification');
        $accountSteps->editAccount($accountId, null, ['passwordtest11222longlong', 'passshort'], ['savepassword' => SAVE_PASSWORD_DATABASE]);
        $I->seeResponseContainsJson(['needUpdate' => $canCheck]);

        $accountSteps->editAccount($accountId, null, ['passshort', null], ['savepassword' => SAVE_PASSWORD_LOCALLY]);
        $I->seeResponseContainsJson(['needUpdate' => false]);

        $accountSteps->deleteLocalPasswords();

        $accountSteps->editAccount($accountId, null, 'somenewpass');
        $I->seeResponseContainsJson(['needUpdate' => $canCheck]);

        $I->wantTo('add account, with local password initially');
        $accountSteps->addAccount($providerId, 'testnewlocalpassword', 'localpassword', ['savepassword' => SAVE_PASSWORD_LOCALLY]);

        $accountSteps->deleteLocalPasswords();
    }
}
