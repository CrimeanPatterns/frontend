<?php

namespace Account;

use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\BusinessTransaction\BalanceWatchStart;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class BalanceWatchCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var Usr */
    protected $business;

    /** @var Usr */
    protected $businessAdmin;
    /** @var Router */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
    }

    public function checkStartMonitored(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'BalanceWatchCredits' => 1,
        ], true);

        $username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $userId]);
        $email = $I->grabFromDatabase('Usr', 'Email', ['UserID' => $userId]);

        $accountId = $I->createAwAccount($userId, 'testprovider', $login = 'fake' . StringUtils::getPseudoRandomString(6), null, [
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 0,
        ]);

        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]) . '?_switch_user=' . $username);
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();

        $I->selectOption(['id' => 'account_login'], 'balance.point');
        $I->checkOption(['id' => 'account_BalanceWatch']);
        $I->selectOption(['id' => 'account_PointsSource'], BalanceWatch::POINTS_SOURCE_PURCHASE);
        $I->submitForm('#account-form', []);

        $I->seeEmailTo($email, 'being monitored for changes', 'is being monitored for changes');
        $I->cantSeeEmailTo($email, '%', '%');

        $accountRow = $I->grabService('doctrine')->getConnection()->executeQuery('SELECT BalanceWatchStartDate FROM Account WHERE AccountID = ?', [$accountId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotNull($accountRow['BalanceWatchStartDate']);
        $I->assertStringContainsString(date('Y-m-d'), $accountRow['BalanceWatchStartDate']);

        $userRow = $I->grabService('doctrine')->getConnection()->executeQuery('SELECT BalanceWatchCredits FROM Usr WHERE UserID = ?', [$userId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(0, $userRow['BalanceWatchCredits']);

        // check disabled option after start
        // $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        // $I->seeElement('input', ['id' => 'account_BalanceWatch', 'disabled' => 'disabled']);
    }

    public function checkDeniedBuyCreditsNotAwPlus(\TestSymfonyGuy $I)
    {
        $userId = $userId = $I->createAwUser();
        $login = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $userId]);
        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase('Usr', 'AccountLevel', ['UserID' => $userId]));
        $I->amOnPage($this->router->generate('aw_users_pay_balancewatchcredit', ['_switch_user' => $login]));
        $I->followMetaRedirect();
        $I->see('Edit my profile');
    }

    public function checkStartMonitoredBusiness(\TestSymfonyGuy $I)
    {
        $this->createBusiness($I);
        $accountId = $I->createAwAccount($this->business->getUserid(), 'testprovider', StringUtils::getRandomCode(8), 'test', [
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 0,
        ]);

        $I->amOnSubdomain('business');
        $I->amOnPage($this->router->generate('aw_business_members') . '?_switch_user=' . $this->businessAdmin->getLogin());
        $I->seeResponseCodeIs(200);
        $I->saveCsrfToken();

        $businessBalance = $this->business->getBusinessInfo()->getBalance();

        $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        $I->selectOption(['id' => 'account_login'], 'balance.point');
        $I->checkOption(['id' => 'account_BalanceWatch']);
        $I->selectOption(['id' => 'account_PointsSource'], BalanceWatch::POINTS_SOURCE_PURCHASE);
        $I->submitForm('#account-form', []);

        $I->seeInDatabase('BalanceWatch', [
            'AccountID' => $accountId,
            'PayerUserID' => $this->businessAdmin->getUserid(),
            'IsBusiness' => 1,
        ]);
        $I->seeInDatabase('BusinessTransaction', [
            'UserID' => $this->business->getUserid(),
            'Type' => BalanceWatchStart::TYPE,
            'Amount' => BalanceWatchCredit::PRICE,
            'Balance' => $businessBalance - BalanceWatchCredit::PRICE,
        ]);
        $I->seeInDatabase('BusinessInfo', [
            'UserID' => $this->business->getUserid(),
            'Balance' => $businessBalance - BalanceWatchCredit::PRICE,
        ]);

        $I->seeEmailTo($this->businessAdmin->getEmail(), 'being monitored for changes', 'is being monitored for changes');
        $I->cantSeeEmailTo($this->business->getEmail(), 'being monitored for changes', 'is being monitored for changes');

        // $I->amOnSubdomain('business');
        // $I->amOnPage($this->router->generate('aw_account_edit', ['accountId' => $accountId]));
        // $I->seeElement('input', ['id' => 'account_BalanceWatch', 'disabled' => 'disabled']);

        return $accountId;
    }

    private function createBusiness(\TestSymfonyGuy $I)
    {
        $userRepository = $I->grabService('doctrine')->getRepository(Usr::class);
        $this->business = $userRepository->find($I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
            'Company' => 'Business' . $I->grabRandomString(8),
        ], true));
        $this->businessAdmin = $userRepository->find($I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ], true));

        $I->haveInDatabase('GroupUserLink', ['SiteGroupID' => 49, 'UserID' => $this->businessAdmin->getUserid()]);
        $I->connectUserWithBusiness($this->businessAdmin->getUserid(), $this->business->getUserid(), ACCESS_ADMIN);
    }
}
