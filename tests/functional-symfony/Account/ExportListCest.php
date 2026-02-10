<?php

namespace Account;

use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\UserSteps;

/**
 * @group frontend-functional
 */
class ExportListCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected $providerId = 636;
    protected $userId;
    protected $username;
    protected $accountId;

    /**
     * @var UserSteps
     */
    protected $userSteps;

    /**
     * @var AccountSteps
     */
    protected $accountSteps;

    protected $db;
    protected $translator;

    public function _before(\TestSymfonyGuy $I)
    {
        $scenario = $I->grabScenarioFrom($I);
        $this->userSteps = new UserSteps($scenario);
        $this->accountSteps = new AccountSteps($scenario);

        $this->userId = $I->createAwUser(null, null, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
        ], true, true);
        $this->username = $I->grabFromDatabase('Usr', 'Login', ['UserID' => $this->userId]);

        $balance = ['Balance' => 100, 'TotalBalance' => 100];
        $I->createAwAccount($this->userId, $this->providerId, 'balance.random', 'password', $balance);
        $I->createAwAccount($this->userId, $this->providerId, 'balance.increase', 'password', $balance);

        $this->translator = $I->grabService('translator');

        $I->amOnPage('/account/list?_switch_user=' . $this->username);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->userId = null;
        $this->username = null;
        $this->providerId = null;
    }

    public function travelPlannerValid(\TestSymfonyGuy $I)
    {
        $I->wantTo('download excel file - travel planner: valid user');
        $I->sendGET($I->getContainer()->get('router')->generate('aw_account_export_type', [
            'type' => 'travelPlanner',
            'format' => 'xls',
        ]));
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('content-type', 'application/vnd.ms-excel');
    }

    public function travelPlannerInValidFormat(\TestSymfonyGuy $I)
    {
        $I->wantTo('download excel file - travel planner: bad format');
        $I->sendGET($I->getContainer()->get('router')->generate('aw_account_export_type', [
            'type' => 'travelPlanner',
            'format' => 'badFormat',
        ]));
        $I->seeResponseCodeIs(200);
        $I->dontSeeHttpHeader('content-type', 'application/vnd.ms-excel');
        $I->see($this->translator->trans('error.award.account.other.title'));
    }

    public function travelPlannerNeedAwplus(\TestSymfonyGuy $I)
    {
        $I->wantTo('download excel file - travel planner: error, not awplus user');
        $db = $I->grabService('doctrine')->getManager()->getConnection();
        $db->executeQuery('UPDATE Usr SET AccountLevel = ' . ACCOUNT_LEVEL_FREE . ' WHERE UserID = ' . $this->userId . ' LIMIT 1');
        $I->amOnPage('/account/list?_switch_user=' . $this->username);

        $I->sendGET($I->getContainer()->get('router')->generate('aw_account_export_type', [
            'type' => 'travelPlanner',
            'format' => 'xls',
        ]));

        $I->seeResponseCodeIs(200);
        $I->dontSeeHttpHeader('content-type', 'application/vnd.ms-excel');
        $I->see($this->translator->trans('please-upgrade'));
    }

    public function travelPlannerNoData(\TestSymfonyGuy $I)
    {
        $I->executeQuery("delete from Account where UserID = " . $this->userId);
        $I->amOnPage($I->getContainer()->get('router')->generate('aw_account_export_type', [
            'type' => 'travelPlanner',
            'format' => 'xls',
        ]));

        $I->see($this->translator->trans('account.export.no-data'));
    }
}
