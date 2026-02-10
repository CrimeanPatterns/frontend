<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\Tests\Modules\Access\AccountAccessScenario;
use AwardWallet\Tests\Modules\Access\Action;
use Codeception\Example;

/**
 * @group frontend-functional
 */
class AutologinControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProvider
     */
    public function testAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var AccountAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        $I->amOnRoute("aw_account_redirect", ["ID" => $scenario->accountId]);

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseCodeIs(200);
                $I->seeInSource($scenario->login);

                break;

            case Action::REDIRECT_TO_LOGIN:
                $I->seeInCurrentUrl("/login?BackTo=");
                $I->dontSeeInSource($scenario->login);

                break;

            default:
                $I->seeResponseCodeIs(403);
                $I->dontSeeInSource($scenario->login);
        }
    }

    public function testCustom403()
    {
    }

    private function dataProvider()
    {
        return AccountAccessScenario::dataProvider();
    }
}
