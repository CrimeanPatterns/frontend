<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\Tests\Modules\Access\AccountAccessScenario;
use AwardWallet\Tests\Modules\Access\Action;
use Codeception\Example;

/**
 * @group frontend-functional
 */
class ManualUpdateControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider dataProviderAccountAccess
     */
    public function testGetAccountInfo(\TestSymfonyGuy $I, Example $example)
    {
        /** @var AccountAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        if ($scenario->authorized) {
            $I->saveCsrfToken();
        }

        $router = $I->grabService('router');
        $I->sendAjaxGetRequest($router->generate('aw_account_list_manual_update', ['id' => $scenario->accountId]));

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseCodeIs(200);
                $I->seeResponseContainsJson(['success' => true]);

                break;

            case Action::FORBIDDEN:
                $I->seeResponseCodeIs(200);
                $I->seeResponseContainsJson(['success' => false, 'message' => 'Access denied.']);

                break;

            case Action::REDIRECT_TO_LOGIN:
                $I->seeResponseCodeIs(403);

                break;
        }
    }

    private function dataProviderAccountAccess()
    {
        return AccountAccessScenario::dataProvider();
    }
}
