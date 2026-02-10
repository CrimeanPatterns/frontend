<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use Codeception\Example;

class ListInfoControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider plusScenarios
     */
    public function testAwPlusBusiness(\TestSymfonyGuy $I, Example $example)
    {
        $businessUserId = $I->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS]);

        if (!$example['plus']) {
            $I->executeQuery("update BusinessInfo set Balance = 0, Discount = 0, PaidUntilDate = adddate(now(), -30) where UserID = $businessUserId");
        }

        $adminUserId = $I->createStaffUserForBusinessUser($businessUserId, ACCESS_ADMIN);
        $adminLogin = $I->grabFromDatabase("Usr", "Login", ["UserID" => $adminUserId]);
        $providerId = $I->createAwProvider(null, null, [], [
            "Parse" => function () {
                $this->SetBalance(100);
                $this->SetProperty("EliteLevel", "myEliteLevel");
            },
        ]);
        $ppId = $I->haveInDatabase("ProviderProperty", ["ProviderID" => $providerId, "Code" => "EliteLevel", "Name" => "Elite Level", "Kind" => PROPERTY_KIND_STATUS, 'SortIndex' => 0]);
        $accountId = $I->createAwAccount($businessUserId, $providerId, "balance.random", null);
        $I->haveInDatabase("AccountProperty", ["AccountID" => $accountId, "ProviderPropertyID" => $ppId, "Val" => "MyEliteLevel"]);
        $I->amOnBusiness();
        $I->amOnPage("/account/info/a$accountId?_switch_user=$adminLogin");
        $I->seeResponseContainsJson([
            "HideProperties" => !$example['plus'],
        ]);
    }

    private function plusScenarios()
    {
        return [
            ['plus' => false],
            ['plus' => true],
        ];
    }
}
