<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;

use function PHPUnit\Framework\assertStringStartsWith;

/**
 * @group mobile
 * @group frontend-functional
 */
class AccountsCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'accounts-', 'userpass-', [], true);
    }

    public function sameProviderAccountLimit(\TestSymfonyGuy $I)
    {
        foreach (range(1, MAX_LIKE_LP_PER_PERSON - 1) as $time) {
            $I->createAwAccount($this->userId, AccountSteps::TEST_PROVIDER_ID, 'balance.random.' . $time);
        }
        $this->accountSteps->addAccount(AccountSteps::TEST_PROVIDER_ID, 'expiration.past');

        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('add', AccountSteps::TEST_PROVIDER_ID));
        $formData['login'] = 'balance.random';
        $formData['notrelated'] = true;

        $I->sendPOST($url, $formData);
        $I->seeResponseContainsJson(['formData' => ['errors' => [
            $error = sprintf("You can't have more than %d accounts for the same provider listed under the same person, please choose another person as the owner of this loyalty program", MAX_LIKE_LP_PER_PERSON),
        ]]]);

        $I->assertEquals(MAX_LIKE_LP_PER_PERSON, $I->grabCountFromDatabase('Account', ['UserID' => $this->userId, 'ProviderID' => AccountSteps::TEST_PROVIDER_ID]));

        $pendingAccountId = $I->createAwAccount($this->userId, AccountSteps::TEST_PROVIDER_ID, 'balance.random.pending', null, ['State' => ACCOUNT_PENDING]);
        $this->assertEditPendingAccountError($I, $pendingAccountId, $error);
    }

    public function disabledAccount(\TestSymfonyGuy $I)
    {
        $account = $I->createAwAccount($this->userId, AccountSteps::TEST_PROVIDER_ID, 'expiration.past', null, ['Disabled' => 1, 'ErrorCode' => ACCOUNT_UNCHECKED]);

        // disabled off
        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $account));
        $I->assertEquals(true, $formData['disabled']);
        $formData['disabled'] = false;
        $this->accountSteps->sendPUT(AccountSteps::getUrl('edit', $account), $formData);
        $I->assertEquals(true, $I->grabDataFromJsonResponse('needUpdate'));
        $I->seeInDatabase('Account', ['AccountID' => $account, 'ErrorCode' => ACCOUNT_UNCHECKED, 'Disabled' => 0]);

        // disabled on
        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $account));
        $I->assertEquals(false, $formData['disabled']);
        $formData['disabled'] = true;
        $this->accountSteps->sendPUT(AccountSteps::getUrl('edit', $account), $formData);
        $I->assertEquals(false, $I->grabDataFromJsonResponse('needUpdate'));
        $I->seeInDatabase('Account', ['AccountID' => $account, 'ErrorCode' => ACCOUNT_UNCHECKED, 'Disabled' => 1]);
    }

    public function maxPersonalAccountLimit(\TestSymfonyGuy $I)
    {
        $providerId = AccountSteps::TEST_PROVIDER_ID;

        foreach (range(1, PERSONAL_INTERFACE_MAX_ACCOUNTS - 1) as $time) {
            if (0 === $time % (MAX_LIKE_LP_PER_PERSON - 1)) {
                $providerId = $I->createAwProvider(
                    'testprovider' . StringHandler::getRandomCode(10),
                    'testprovid' . StringHandler::getRandomCode(10),
                    ['PasswordRequired' => 1]
                );
            }
            $I->createAwAccount($this->userId, $providerId, 'balance.random.' . $time);
        }

        $this->accountSteps->addAccount(AccountSteps::TEST_PROVIDER_ID, 'expiration.past');

        $formData = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('add', AccountSteps::TEST_PROVIDER_ID));
        $formData['login'] = 'balance.random';
        $formData['notrelated'] = true;

        $I->sendPOST($url, $formData);
        assertStringStartsWith(
            $error = 'AwardWallet.com is a personal interface for managing loyalty programs and is not intended for business use.',
            $I->grabDataFromJsonResponse('formData.errors.0')
        );

        $I->assertEquals(PERSONAL_INTERFACE_MAX_ACCOUNTS, $I->grabCountFromDatabase('Account', ['UserID' => $this->userId]));

        // pending account
        $pendingAccountId = $I->createAwAccount($this->userId, $I->createAwProvider(
            'testprovider' . StringHandler::getRandomCode(10),
            'testprovid' . StringHandler::getRandomCode(10),
            ['PasswordRequired' => 1]
        ), 'balance.random.pending', null, ['State' => ACCOUNT_PENDING]);
        $this->assertEditPendingAccountError($I, $pendingAccountId, $error);
    }

    public function canEditAccountFromRetailProvider(\TestSymfonyGuy $I)
    {
        $retailProviderId = $I->createAwProvider(
            'retlprovider' . StringHandler::getRandomCode(10),
            'testprovid' . StringHandler::getRandomCode(10),
            [
                'PasswordRequired' => 1,
                'State' => PROVIDER_RETAIL,
            ]
        );

        $account = $I->createAwAccount($this->userId, $retailProviderId, 'expiration.past', null, ['ErrorCode' => ACCOUNT_UNCHECKED]);
        $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $account));
    }

    private function assertEditPendingAccountError(\TestSymfonyGuy $I, int $accountId, string $error)
    {
        $url = AccountSteps::getUrl('edit', $accountId);
        $this->accountSteps->sendPUT($url, $this->accountSteps->loadAccountForm($url));
        assertStringStartsWith(
            $error,
            $I->grabDataFromJsonResponse('formData.errors.0')
        );
    }
}
