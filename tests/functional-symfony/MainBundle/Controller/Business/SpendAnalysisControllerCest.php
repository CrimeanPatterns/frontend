<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Business;

use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\SpendAnalysisAccessScenario;
use Codeception\Example;

/**
 * @group frontend-functional
 */
class SpendAnalysisControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        $I->fillMileValueData();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var SpendAnalysisAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        if ($scenario->authorizedBusiness) {
            $I->amOnBusiness();
        }

        if ($scenario->victimConnectionId) {
            $I->amOnRoute('aw_spent_analysis_business', ['agentId' => $scenario->victimConnectionId]);
        } elseif ($scenario->attackerConnectionId) {
            $I->amOnRoute('aw_spent_analysis_business', ['agentId' => $scenario->attackerConnectionId]);
        }

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseCodeIs(200);

                break;

            case Action::REDIRECT_TO_LOGIN:
                $I->seeInCurrentUrl('/login?BackTo=');

                break;

            case Action::FORBIDDEN:
                $I->seeResponseCodeIs(403);

                break;
        }
    }

    private function dataProvider()
    {
        return SpendAnalysisAccessScenario::dataProvider();
    }
}
