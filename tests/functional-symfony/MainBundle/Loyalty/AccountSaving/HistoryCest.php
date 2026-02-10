<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryField;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryRow;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use Codeception\Example;
use Ramsey\Uuid\Uuid;

/**
 * @group frontend-functional
 */
class HistoryCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $user;
    /**
     * @var AccountProcessor
     */
    private $accountProcessor;
    /**
     * @var int
     */
    private $accountId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $provider = 'testprovider';
        $this->accountId = $I->createAwAccount($this->user->getUserid(), $provider, "login1");

        $columns = [
            'Activity Date' => 'PostingDate',
            'Description' => 'Description',
            'Award Miles' => 'Miles',
        ];
        $this->accountProcessor = $I->grabService(AccountProcessor::class);
    }

    public function testTripLinking(\TestSymfonyGuy $I)
    {
        /** @var AccountRepository $accountRepo */
        $accountRepo = $I->grabService('doctrine')->getRepository(Account::class);
        /** @var Account $account */
        $account = $accountRepo->find($this->accountId);

        $testProviderId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]);
        $tripId = $I->haveInDatabase("Trip", [
            'UserID' => $this->user->getUserid(),
            'IssuingAirlineConfirmationNumber' => '4NZ123',
            'ProviderID' => $testProviderId,
            'Category' => Trip::CATEGORY_AIR,
            'AccountID' => $this->accountId,
        ]);

        $history = new History();
        $history
            ->setRows([
                new HistoryRow([
                    new HistoryField("Activity Date", strtotime("-1 month")),
                    new HistoryField("Description", "CLASSIC AWARD POINTS DEDUCTION 1 PASSENGER REF 4NZ123"),
                    new HistoryField("Award Miles", -50000),
                ]),
            ])
            ->setRange(History::HISTORY_COMPLETE)
        ;

        $response = new CheckAccountResponse();
        $response
            ->setUserdata(new UserData($this->accountId))
            ->setCheckdate(new \DateTime())
            ->setRequestdate(new \DateTime())
            ->setHistory($history)
        ;

        $this->accountProcessor->saveAccount($account, $response);

        $I->assertEquals(1, $I->grabCountFromDatabase("AccountHistory", ["AccountID" => $this->accountId]));
        $I->seeInDatabase("HistoryToTripLink", ["TripID" => $tripId]);
    }

    /**
     * @dataProvider rangesDataProvider
     */
    public function testRanges(\TestSymfonyGuy $I, Example $example)
    {
        /** @var AccountRepository $accountRepo */
        $accountRepo = $I->grabService('doctrine')->getRepository(Account::class);
        /** @var Account $account */
        $account = $accountRepo->find($this->accountId);

        for ($n = 1; $n <= 3; $n++) {
            $I->haveInDatabase("AccountHistory", [
                "UUID" => StringHandler::uuid(),
                "AccountID" => $this->accountId,
                "PostingDate" => sprintf("2020-01-%02d", $n),
                "Description" => "Orig $n",
                "Miles" => $n + 100,
                "Position" => 1,
                "Amount" => $n + 10,
            ]);
        }

        $history = new History();
        $rows = [];
        $date = $example["startDate"];

        for ($n = 1; $n <= $example["rowCount"]; $n++) {
            $rows[] = new HistoryRow([
                new HistoryField("Activity Date", strtotime($date)),
                new HistoryField("Description", "Update $n"),
                new HistoryField("Award Miles", -100 * $n),
            ]);
            $date = date("Y-m-d", strtotime("tomorrow", strtotime($date)));
        }

        $history
            ->setRows($rows)
            ->setRange($example['range'])
        ;

        $response = new CheckAccountResponse();
        $response
            ->setUserdata(new UserData($this->accountId))
            ->setCheckdate(new \DateTime())
            ->setRequestdate(new \DateTime())
            ->setHistory($history)
        ;

        $this->accountProcessor->saveAccount($account, $response);
        $rows = implode(", ", $I->query("select Description from AccountHistory where AccountID = ?", [$this->accountId])->fetchAll(\PDO::FETCH_COLUMN));

        $I->assertEquals(
            $example['expectedRows'],
            $rows
        );
    }

    public function testKeepOldWhenNoHistory(\TestSymfonyGuy $I)
    {
        /** @var AccountRepository $accountRepo */
        $accountRepo = $I->grabService('doctrine')->getRepository(Account::class);
        /** @var Account $account */
        $account = $accountRepo->find($this->accountId);

        $subAccountId = $I->haveInDatabase("SubAccount", [
            "AccountID" => $this->accountId,
            "Code" => "sub1",
            "DisplayName" => "SubAccount1",
            "Balance" => 10,
        ]);

        $I->haveInDatabase("AccountHistory", [
            "UUID" => Uuid::uuid4(),
            "AccountID" => $this->accountId,
            "SubAccountID" => $subAccountId,
            "PostingDate" => "2000-01-01 00:00:00",
            "Description" => "Existing tx",
            "Miles" => 1000,
            "Position" => 1,
            'Amount' => 100,
        ]);

        $response = new CheckAccountResponse();
        $response
            ->setUserdata(new UserData($this->accountId))
            ->setCheckdate(new \DateTime())
            ->setRequestdate(new \DateTime())
        ;

        $this->accountProcessor->saveAccount($account, $response);

        $I->assertEquals(1, $I->grabCountFromDatabase("AccountHistory", ["AccountID" => $this->accountId, "SubAccountID" => $subAccountId]));
    }

    private function rangesDataProvider()
    {
        return [
            ['rowCount' => 1, 'range' => 'complete', 'startDate' => '2020-05-01', 'expectedRows' => 'Update 1'],
            ['rowCount' => 2, 'range' => 'complete', 'startDate' => '2020-01-02', 'expectedRows' => 'Update 1, Update 2'],
            ['rowCount' => 0, 'range' => 'complete', 'startDate' => '2020-01-01', 'expectedRows' => 'Orig 1, Orig 2, Orig 3'],

            ['rowCount' => 1, 'range' => 'incremental', 'startDate' => '2020-01-03', 'expectedRows' => 'Orig 1, Orig 2, Update 1'],
            ['rowCount' => 1, 'range' => 'incremental', 'startDate' => '2020-01-05', 'expectedRows' => 'Orig 1, Orig 2, Orig 3, Update 1'],
            ['rowCount' => 2, 'range' => 'incremental', 'startDate' => '2020-01-05', 'expectedRows' => 'Orig 1, Orig 2, Orig 3, Update 1, Update 2'],
            ['rowCount' => 2, 'range' => 'incremental', 'startDate' => '2020-01-02', 'expectedRows' => 'Orig 1, Update 1, Update 2'],
            ['rowCount' => 3, 'range' => 'incremental', 'startDate' => '2019-12-01', 'expectedRows' => 'Update 1, Update 2, Update 3'],

            ['rowCount' => 1, 'range' => 'incremental2', 'startDate' => '2020-01-03', 'expectedRows' => 'Orig 1, Orig 2, Update 1'],
            ['rowCount' => 1, 'range' => 'incremental2', 'startDate' => '2020-01-05', 'expectedRows' => 'Orig 1, Orig 2, Orig 3, Update 1'],
            ['rowCount' => 2, 'range' => 'incremental2', 'startDate' => '2020-01-05', 'expectedRows' => 'Orig 1, Orig 2, Orig 3, Update 1, Update 2'],
            ['rowCount' => 2, 'range' => 'incremental2', 'startDate' => '2020-01-02', 'expectedRows' => 'Orig 1, Update 1, Update 2'],
            ['rowCount' => 3, 'range' => 'incremental2', 'startDate' => '2019-12-01', 'expectedRows' => 'Update 1, Update 2, Update 3'],
        ];
    }
}
