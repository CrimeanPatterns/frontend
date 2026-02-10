<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Codeception\Module\Aw;

/**
 * @group mobile
 * @group frontend-functional
 */
class EditDeleteCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var int
     */
    protected $providerId;
    /**
     * @var int
     */
    private $accountId;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'acc-edit-', Aw::DEFAULT_PASSWORD, [], true);

        $this->providerId = $I->createAwProvider(
            'testprovider' . StringHandler::getRandomCode(10),
            'testprovid' . StringHandler::getRandomCode(10),
            ['PasswordRequired' => 1]
        );

        $this->accountId = $this->accountSteps->createAwAccount($this->userId, $this->providerId, 'login1', 'pass');
    }

    public function emptyLoginShouldFail()
    {
        $this->accountSteps->editAccount($this->accountId, '', ['pass', null], [], [['formData.children.1', self::TRANS_VALUE_BLANK]]);
    }

    public function emptyPasswordShouldFail()
    {
        $this->accountSteps->editAccount($this->accountId, null, ['pass', ''], [], [['formData.children.2', self::TRANS_VALUE_BLANK]]);
    }

    public function passwordWithSpacesShouldFail()
    {
        $this->accountSteps->editAccount($this->accountId, null, ['pass', '    '], [], [['formData.children.2', self::TRANS_VALUE_BLANK]]);
    }

    public function invalidUserAgentShouldFail()
    {
        $this->accountSteps->editAccount($this->accountId, null, ['pass', null], ['owner' => -100], ['formData.children.0']);
    }

    public function emptyLoginAndPasswordShouldFail()
    {
        $this->accountSteps->editAccount($this->accountId, '', ['pass', ''], [], [
            ['formData.children.1', self::TRANS_VALUE_BLANK],
            ['formData.children.2', self::TRANS_VALUE_BLANK],
        ]);
    }

    public function changeLoginPasswordShouldNotBeSent(\TestSymfonyGuy $I)
    {
        $this->accountSteps->editAccount($this->accountId, 'login12', ['pass', null]);
        $I->seeResponseContainsJson(['needUpdate' => true]);
    }

    public function changePassword(\TestSymfonyGuy $I)
    {
        $this->accountSteps->editAccount($this->accountId, null, ['pass', 'newpass']);
        $I->seeResponseContainsJson(['needUpdate' => true]);
    }

    public function passwordWithAsterisks(\TestSymfonyGuy $I)
    {
        $this->accountSteps->editAccount($this->accountId, null, ['pass', '********']);
        $I->seeResponseContainsJson(['needUpdate' => true]);
    }

    public function changeLoginAndPassword(\TestSymfonyGuy $I)
    {
        $this->accountSteps->editAccount($this->accountId, 'login32', ['pass', 'pass1']);
        $I->seeResponseContainsJson(['needUpdate' => true]);
    }

    public function changeAccountOwner(\TestSymfonyGuy $I)
    {
        $familyMemberId = $this->userSteps->addFamilyMember($this->userId);
        $this->accountSteps->editAccount($this->accountId, null, null, ['owner' => $this->userId . '_' . $familyMemberId]);
        $I->seeResponseContainsJson(['needUpdate' => false]);

        $this->accountSteps->editAccount($this->accountId, null, ['pass', 'pass12']);
        $I->seeResponseContainsJson(['needUpdate' => true]);
    }

    public function addAccountWithExistingLoginShouldFail(\TestSymfonyGuy $I)
    {
        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('add', $this->providerId));
        $formData['login'] = 'login1';
        $formData['pass'] = 'pass12';
        $formData['notrelated'] = true;

        $I->sendPOST($url, $formData);
        $I->seeResponseContainsJson([
            'existingAccountId' => "{$this->accountId}",
            'login' => 'login1',
        ]
        );
        $I->assertEquals(1, $I->grabCountFromDatabase('Account', ['UserID' => $this->userId, 'Login' => 'login1']));
    }

    public function changeAccountToExistingLoginShouldFail(\TestSymfonyGuy $I)
    {
        $accountId = $this->accountSteps->createAwAccount($this->userId, $this->providerId, 'login2', 'pass');

        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $this->accountId));
        $formData['login'] = 'login2';
        $formData['notrelated'] = true;
        unset($formData['Pass']);

        $I->sendPUT($url, $formData);
        $I->seeResponseContainsJson([
            'existingAccountId' => "{$accountId}",
            'login' => 'login2',
        ]
        );

        $I->assertEquals(1, $I->grabCountFromDatabase('Account', ['UserID' => $this->userId, 'Login' => 'login1']));
        $I->assertEquals(1, $I->grabCountFromDatabase('Account', ['UserID' => $this->userId, 'Login' => 'login2']));
    }

    public function deleteAccount()
    {
        $this->accountSteps->deleteAccount($this->accountId);
    }
}
