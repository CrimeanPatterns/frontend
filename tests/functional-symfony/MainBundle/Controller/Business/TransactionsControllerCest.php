<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Business;

use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\SpendAnalysisAccessScenario;
use Codeception\Example;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group frontend-functional
 */
class TransactionsControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I): void
    {
        $I->fillMileValueData();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAccessList(\TestSymfonyGuy $I, Example $example): void
    {
        $this->testAccess($I, $example, 'aw_transactions_business_agent');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAccessData(\TestSymfonyGuy $I, Example $example): void
    {
        $this->testAccess($I, $example, 'aw_transactions_business_data');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAccessTotals(\TestSymfonyGuy $I, Example $example): void
    {
        $this->testAccess($I, $example, 'aw_transactions_business_totals');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAccessExport(\TestSymfonyGuy $I, Example $example): void
    {
        $this->testAccess($I, $example, 'aw_transactions_business_export_csv');
    }

    private function testAccess(\TestSymfonyGuy $I, Example $example, string $route): void
    {
        /** @var SpendAnalysisAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        if ($scenario->authorizedBusiness) {
            $I->amOnBusiness();
        }

        if ($scenario->victimConnectionId) {
            $I->amOnRoute($route, ['agentId' => $scenario->victimConnectionId]);
        } elseif ($scenario->attackerConnectionId) {
            $I->amOnRoute($route, ['agentId' => $scenario->attackerConnectionId]);
        }

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseCodeIs(Response::HTTP_OK);

                break;

            case Action::REDIRECT_TO_LOGIN:
                $I->seeInCurrentUrl('/login?BackTo=');

                break;

            case Action::FORBIDDEN:
                $I->seeResponseCodeIs(Response::HTTP_FORBIDDEN);

                break;
        }
    }

    private function dataProvider(): array
    {
        return SpendAnalysisAccessScenario::dataProvider();
    }
}
