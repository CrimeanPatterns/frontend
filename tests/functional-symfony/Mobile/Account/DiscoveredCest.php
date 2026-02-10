<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;

/**
 * @group mobile
 * @group frontend-functional
 */
class DiscoveredCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'discaccs-', 'userpass-', [], true);
    }

    public function emptyFormShouldNotContainExtraFields(\TestSymfonyGuy $I)
    {
        $providerId = $I->createAwProvider(
            'testprovider' . StringHandler::getRandomCode(10),
            'testprovid' . StringHandler::getRandomCode(10)
        );
        $accountId = $I->createAwAccount(
            $this->userId,
            $providerId,
            'login',
            '',
            ['State' => ACCOUNT_PENDING]
        );
        $form = $this->accountSteps->loadAccountForm($url = AccountSteps::getUrl('edit', $accountId));
        $I->assertEquals(
            [
                'owner' => $this->userId,
                'login' => 'login',
                'pass' => '',
                'savepassword' => '1',
                'notrelated' => true,
                'useragents' => [],
                '_token' => $form['_token'],
            ],
            $form
        );
    }
}
