<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\Modules\Access\AccountAccessScenario;
use AwardWallet\Tests\Modules\Access\Action;
use AwardWallet\Tests\Modules\Access\TimelineWriteAccessScenario;
use Codeception\Example;
use Monolog\Logger;

/**
 * @group frontend-functional
 */
class ExtensionControllerCest
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

        $providerId = $I->createAwProvider(null, null, [
            'AutoLogin' => AUTOLOGIN_EXTENSION,
        ]);
        $I->executeQuery("update Account set ProviderID = $providerId where AccountID = {$scenario->accountId}");

        if ($scenario->expectedAction !== Action::REDIRECT_TO_LOGIN) {
            $I->saveCsrfToken();
        }
        $I->sendPOST($I->grabService("router")->generate("aw_account_extension_browsercheck", ["accountId" => $scenario->accountId]));
        $I->seeResponseCodeIs(200);

        switch ($scenario->expectedAction) {
            case Action::ALLOWED:
                $I->seeResponseContainsJson(["password" => "some_account_password"]);

                break;

            case Action::REDIRECT_TO_LOGIN:
                $I->seeInCurrentUrl("/login?BackTo=");
                $I->dontSeeInSource("some_account_password");

                break;

            default:
                $I->seeResponseContainsJson(["error" => 'Access denied']);
                $I->dontSeeResponseContainsJson(["password" => "some_account_password"]);
        }
    }

    public function testNoRefererNoAccess(\TestSymfonyGuy $I)
    {
        $haveWarning = false;
        $logger = $I->stubMakeEmpty(Logger::class, [
            'warning' => function ($message, $context) use (&$haveWarning) {
                if ($message === 'missing referer on password request') {
                    $haveWarning = true;
                }
            },
        ]);
        $I->mockService('logger', $logger);

        $login = "login" . StringUtils::getRandomCode(10);
        $userId = $I->createAwUser($login, null, [], true);
        $providerId = $I->createAwProvider(null, null, [
            'AutoLogin' => AUTOLOGIN_EXTENSION,
        ]);
        $accountId = $I->createAwAccount($userId, $providerId, "sometest");

        $I->amOnPage("/test/client-info?_switch_user=$login");
        $I->saveCsrfToken();
        $I->clearHistory(); // remove referer
        $I->sendPOST($I->grabService("router")->generate("aw_account_extension_browsercheck", ["accountId" => $accountId]));
        $I->seeResponseCodeIs(200);
        $I->assertTrue($haveWarning);
    }

    /**
     * @dataProvider saveByConfNoAccessDataProvider
     */
    public function testSaveByConfNoAccess(\TestSymfonyGuy $I, Example $example)
    {
        /** @var TimelineWriteAccessScenario $scenario */
        $scenario = $example['scenario'];
        $scenario->create($I);
        $scenario->wantToTest($I);

        if ($scenario->authorized) {
            $I->saveCsrfToken();
        }

        $depDate = round(strtotime("+1 day") / 60) * 60;
        $arrDate = $depDate + 3600 * 2;

        parse_str('login=qwerty&password=qwerty&ConfNo=43243242&FirstName=Vladimir&LastName=Silantyev&accountId=0&providerCode=testextension&mode=confirmation&properties%5BItineraries%5D%5B0%5D%5BRecordLocator%5D=RECLOC1&properties%5BItineraries%5D%5B0%5D%5BTripSegments%5D%5B0%5D%5BFlightNumber%5D=3223&properties%5BItineraries%5D%5B0%5D%5BTripSegments%5D%5B0%5D%5BAirlineName%5D=TestExt+Airlines&properties%5BItineraries%5D%5B0%5D%5BTripSegments%5D%5B0%5D%5BDepCode%5D=JFK&properties%5BItineraries%5D%5B0%5D%5BTripSegments%5D%5B0%5D%5BArrCode%5D=LAX&properties%5BItineraries%5D%5B0%5D%5BTripSegments%5D%5B0%5D%5BDepDate%5D=' . $depDate . '&properties%5BItineraries%5D%5B0%5D%5BTripSegments%5D%5B0%5D%5BArrDate%5D=' . $arrDate . '&properties%5BItineraries%5D%5B0%5D%5BTripSegments%5D%5B0%5D%5BDuration%5D=2h+10m&errorMessage=&errorCode=&goto=&selectedUserId=' . $scenario->victimId . '&familyMemberId=' . $scenario->familyMemberId . '&clientId=' . ($scenario->familyMemberId ?? $scenario->victimConnectionId), $request);

        $I->sendAjaxPostRequest($I->grabService("router")->generate("aw_account_extension_receive_by_confirmation"), $request);

        if ($scenario->expectedAction !== Action::REDIRECT_TO_LOGIN) {
            $I->seeResponseCodeIs(200);
        }

        switch ($scenario->expectedAction) {
            case Action::REDIRECT_TO_LOGIN:
                $I->seeResponseCodeIs(403);
                $I->seeResponseContains('unauthorized');

                break;

            case Action::ALLOWED:
                $I->seeResponseContainsJson([
                    "answer" => "ok",
                ]);
                $trip = $I->query("select * from Trip where UserID = {$scenario->victimId}")->fetch(\PDO::FETCH_ASSOC);
                $I->assertEquals('RECLOC1', $trip['RecordLocator']);
                $I->assertEquals($scenario->familyMemberId, $trip['UserAgentID']);

                break;

            default:
                $I->seeResponseContainsJson(["message" => 'Access denied']);
        }
    }

    public function testHistoryBrowserCheck(\TestSymfonyGuy $I)
    {
        $subAcc1lastHistoryItemTms = 1445904000;
        $subAcc2lastHistoryItemTms = 1445904000 + 86400;
        $mainAccLastHistoryItemTms = 1445904000 + 86400 * 2;
        $login = "login" . StringUtils::getRandomCode(10);
        $userId = $I->createAwUser($login, null, [], true);
        $providerCode = 'provider' . StringUtils::getRandomCode(12);
        $providerId = $I->createAwProvider(null, $providerCode, ['AutoLogin' => AUTOLOGIN_EXTENSION, "CacheVersion" => 22], [
            'GetHistoryColumns' => function () {
                return [
                    "Type" => "Info",
                    "Eligible Nights" => "Info",
                    "Post Date" => "PostingDate",
                    "Description" => "Description",
                    "Starpoints" => "Miles",
                    "Bonus" => "Bonus",
                ];
            },
            'Parse' => function () use ($subAcc1lastHistoryItemTms, $providerCode, $subAcc2lastHistoryItemTms) {
                /** @var $this \TAccountChecker */
                $this->SetBalance(10);

                $subAccsHistory = [
                    'SubAcc1' => [
                        [
                            'Post Date' => $subAcc1lastHistoryItemTms,
                            'Type' => 'Bonus',
                            'Eligible Nights' => '-',
                            'Bonus' => '+2,500',
                            'Description' => 'Subacc1 hist 1',
                        ],
                        [
                            'Post Date' => 1439424000,
                            'Type' => 'Award',
                            'Eligible Nights' => '-',
                            'Starpoints' => '-2,500',
                            'Description' => 'Subacc1 hist 2',
                        ],
                    ],
                    'SubAcc2' => [
                        [
                            'Post Date' => $subAcc2lastHistoryItemTms,
                            'Type' => 'Bonus',
                            'Eligible Nights' => '-',
                            'Bonus' => '+2,500',
                            'Description' => 'Subacc2 hist 1',
                        ],
                        [
                            'Post Date' => 1439424000,
                            'Type' => 'Award',
                            'Eligible Nights' => '-',
                            'Starpoints' => '-2,500',
                            'Description' => 'Subacc2 hist 2',
                        ],
                    ],
                ];

                $this->AddSubAccount([
                    "Balance" => 1,
                    "Code" => "{$providerCode}SubAcc1",
                    "DisplayName" => "SubAccount 1",
                    "HistoryRows" => $subAccsHistory['SubAcc1'],
                ]);
                $this->AddSubAccount([
                    "Balance" => 2,
                    "Code" => "{$providerCode}SubAcc2",
                    "DisplayName" => "SubAccount 2",
                    "HistoryRows" => $subAccsHistory['SubAcc2'],
                ]);
            },
            'ParseHistory' => function ($startDate = null) use ($mainAccLastHistoryItemTms) {
                return [[
                    'Post Date' => $mainAccLastHistoryItemTms,
                    'Type' => 'Bonus',
                    'Eligible Nights' => '-',
                    'Bonus' => '+2,500',
                    'Description' => 'Subacc2 hist 1',
                ]];
            },
        ]);
        $providerCacheVersion = $I->grabFromDatabase("Provider", "CacheVersion", ["ProviderID" => Provider::CHASE_ID]);
        $accountId = $I->createAwAccount($userId, $providerId, "sometest", null, ["HistoryVersion" => $providerCacheVersion]);
        $I->checkAccount($accountId);
        $I->updateInDatabase("Account", ["HistoryVersion" => $providerCacheVersion], ["AccountID" => $accountId]);

        $I->amOnPage("/test/client-info?_switch_user=$login");
        $I->saveCsrfToken();
        $I->clearHistory(); // remove referer
        $I->updateInDatabase('Account', ['ProviderID' => Provider::CHASE_ID], ['AccountID' => $accountId]);
        $I->sendPOST($I->grabService("router")->generate("aw_account_extension_browsercheck", ["accountId" => $accountId]));
        $I->seeResponseContainsJson(['subAccountHistoryStartDate' => [$providerCode . 'SubAcc1' => $subAcc1lastHistoryItemTms, $providerCode . 'SubAcc2' => $subAcc2lastHistoryItemTms]]);
        $I->seeResponseContainsJson(['historyStartDate' => $mainAccLastHistoryItemTms]);

        $I->updateInDatabase("Account", ["HistoryVersion" => $providerCacheVersion - 1], ["AccountID" => $accountId]);
        $I->sendPOST($I->grabService("router")->generate("aw_account_extension_browsercheck", ["accountId" => $accountId]));
        $I->dontSeeResponseContainsJson(['subAccountHistoryStartDate' => [$providerCode . 'SubAcc1' => $subAcc1lastHistoryItemTms, $providerCode . 'SubAcc2' => $subAcc2lastHistoryItemTms]]);
        $I->seeResponseContainsJson(['historyStartDate' => 0]);
    }

    private function dataProvider()
    {
        return AccountAccessScenario::dataProvider();
    }

    private function saveByConfNoAccessDataProvider()
    {
        return TimelineWriteAccessScenario::dataProvider();
    }
}
