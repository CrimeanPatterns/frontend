<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\User;

/**
 * @group frontend-functional
 */
class PayControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function testBusiness(\TestSymfonyGuy $I)
    {
        $I->amOnBusiness();
        $businessUserId = $I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS]);
        $I->executeQuery("update BusinessInfo set Balance = 0, Discount = 0, PaidUntilDate = adddate(now(), -30) where UserID = $businessUserId");
        $adminUserId = $I->createStaffUserForBusinessUser($businessUserId, ACCESS_ADMIN);
        $adminLogin = $I->grabFromDatabase("Usr", "Login", ["UserID" => $adminUserId]);

        $I->amOnPage("/user/pay?_switch_user=$adminLogin");
        $I->seeCurrentUrlEquals("/balance");
        $I->see("Current Remaining AwardWallet Credit");
    }

    public function testPersonal(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $login = $I->grabFromDatabase("Usr", "Login", ["UserID" => $userId]);
        $I->amOnPage("/user/pay?_switch_user=$login");
        $I->seeCurrentUrlEquals("/user/pay");
        $I->see("AwardWallet Plus subscription set up");
    }
}
