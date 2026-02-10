<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Updater\Engine\Local;
use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Updater;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\HistoryProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryColumn;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use AwardWallet\MainBundle\Service\CheckerFactory;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use Codeception\Module\Aw;
use Codeception\Util\Stub;
use Doctrine\DBAL\Logging\LoggerChain;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class HistoryTest extends BaseUserTest
{
    /* нерабочий тест, нужно переписывать AwModule на проверку через updater/Local */
    public function __testHiddenFields()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'History.HiddenFields');
        $this->aw->checkAccount($accountId, false, true, true);
        $subAccID = $this->db->query("select SubAccountID from SubAccount where AccountID = $accountId and Code = 'SubAcc1'")->fetchColumn(0);
        $history = $this->db->query("select AccountID, PostingDate, Description, Miles, Info, Position
		from AccountHistory where AccountID = $accountId and SubAccountID = $subAccID order by PostingDate DESC, Position ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertEquals(1, 1);
    }

    public function testSavePerformance()
    {
        global $Connection;

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "history", "6000");
        $checker = $this->container->get(CheckerFactory::class)->getAccountChecker("testprovider");

        $rows = [];

        for ($n = 0; $n < 1000; $n++) {
            $date = strtotime("2000-01-01") + SECONDS_PER_DAY * $n;

            if (!isset($startDate) || $date >= $startDate) {
                $rows[] = [
                    "No." => $n,
                    "Activity Date" => $date,
                    "Activity" => "Activity $n",
                    "Description" => "Description $n",
                    "Award Miles" => $n * 10,
                ];
            }
        }

        $sqlCounter = $this->container->get("aw.sql.counter");
        /** @var LoggerChain $logger */
        $logger = $this->container->get("database_connection")->getConfiguration()->getSQLLogger();
        $logger->addLogger($sqlCounter);
        $count = $sqlCounter->getCount();
        saveAccountHistory($accountId, $checker->GetHistoryColumns(), $rows, false);
        self::assertEquals(11, $sqlCounter->getCount() - $count);
        self::assertEquals(1000, $this->db->grabCountFromDatabase("AccountHistory", ["AccountID" => $accountId]));

        $count = $sqlCounter->getCount();
        saveAccountHistory($accountId, $checker->GetHistoryColumns(), $rows, false);
        self::assertEquals(1, $sqlCounter->getCount() - $count);
        self::assertEquals(1000, $this->db->grabCountFromDatabase("AccountHistory", ["AccountID" => $accountId]));

        $row = array_pop($rows);
        $count = $sqlCounter->getCount();
        saveAccountHistory($accountId, $checker->GetHistoryColumns(), $rows, false);
        self::assertEquals(1, $sqlCounter->getCount() - $count);
        self::assertEquals(1000, $this->db->grabCountFromDatabase("AccountHistory", ["AccountID" => $accountId]));

        $rows[] = $row;
        $count = $sqlCounter->getCount();
        saveAccountHistory($accountId, $checker->GetHistoryColumns(), $rows, false);
        self::assertEquals(1, $sqlCounter->getCount() - $count);
        self::assertEquals(1000, $this->db->grabCountFromDatabase("AccountHistory", ["AccountID" => $accountId]));
        $Connection->Tracing = false;
    }

    public function testUpdateLastCheckHistoryDate()
    {
        $this->mockCommunicator('testprovider', 'history');
        $this->assertEquals(1, $this->db->grabFromDatabase("Provider", "CanCheckHistory", ["Code" => "testprovider"]));
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'history', 'test');
        $date = $this->db->grabFromDatabase('Account', 'LastCheckHistoryDate', ['AccountID' => $accountId]);
        $this->assertNull($date);
        $this->aw->checkAccount($accountId);
        $this->assertNotNull($this->db->grabFromDatabase('Account', 'HistoryVersion', ['AccountID' => $accountId]));

        $date = new \DateTime("-1 day");
        $this->db->executeQuery('UPDATE Account SET LastCheckHistoryDate = FROM_UNIXTIME(' . $date->getTimestamp() . ') WHERE AccountID = ' . $accountId);
        $this->aw->checkAccount($accountId);
        $this->assertTrue($date->getTimestamp() < strtotime($this->db->grabFromDatabase('Account', 'LastCheckHistoryDate', ['AccountID' => $accountId])));

        // empty history
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'future.rental', 'test');
        $this->db->executeQuery('UPDATE Account SET LastCheckHistoryDate = FROM_UNIXTIME(' . $date->getTimestamp() . ') WHERE AccountID = ' . $accountId);
        $this->aw->checkAccount($accountId);
        $this->assertEquals($date->getTimestamp(), strtotime($this->db->grabFromDatabase('Account', 'LastCheckHistoryDate', ['AccountID' => $accountId])));
    }

    public function testBonus()
    {
        $this->mockCommunicator('testprovider', 'History.Bonus');
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'History.Bonus');
        $this->aw->checkAccount($accountId, false, true, true);
        $history = $this->db->query("select AccountID, PostingDate, Description, Miles, Info, Position
		from AccountHistory where AccountID = $accountId order by PostingDate DESC, Position ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $expected = [
            [
                'AccountID' => strval($accountId),
                'PostingDate' => '2015-10-27 00:00:00',
                'Description' => 'PURCHASED POINTS-MEMBER SELF',
                'Miles' => null,
                'Info' => 'a:3:{s:4:"Type";s:5:"Bonus";s:15:"Eligible Nights";s:1:"-";s:5:"Bonus";s:6:"+2,500";}',
                'Position' => '0',
            ],
            [
                'AccountID' => strval($accountId),
                'PostingDate' => '2015-08-13 00:00:00',
                'Description' => 'SINGAPORE AIRLINES KRISFLYER 2',
                'Miles' => '-2500',
                'Info' => 'a:3:{s:4:"Type";s:5:"Award";s:15:"Eligible Nights";s:1:"-";s:5:"Bonus";s:0:"";}',
                'Position' => '1',
            ],
            [
                'AccountID' => strval($accountId),
                'PostingDate' => '2015-08-13 00:00:00',
                'Description' => 'SINGAPORE AIRLINES KRISFLYER 1',
                'Miles' => '-2400',
                'Info' => 'a:3:{s:4:"Type";s:5:"Award";s:15:"Eligible Nights";s:1:"-";s:5:"Bonus";s:0:"";}',
                'Position' => '2',
            ],
        ];
        $this->assertEquals($expected, $history);

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $info = $this->container->get('aw.accountinfo');
        $historyForPopup = $info->getAccountHistory($account, 20);
        $expected = [
            'columns' => ['Post Date', 'Description', 'Type', 'Eligible Nights', 'Starpoints'],
            'data' => [
                [
                    ["value" => "10/27/15", "type" => "string"],
                    ["value" => "PURCHASED POINTS-MEMBER SELF", "type" => "string"],
                    ["value" => "Bonus", "type" => "string"],
                    ["value" => "-", "type" => "string"],
                    ["value" => "+2,500", "type" => "miles"],
                ],
                [
                    ["value" => "8/13/15", "type" => "string"],
                    ["value" => "SINGAPORE AIRLINES KRISFLYER 2", "type" => "string"],
                    ["value" => "Award", "type" => "string"],
                    ["value" => "-", "type" => "string"],
                    ["value" => "-2,500", "type" => "miles"],
                ],
                [
                    ["value" => "8/13/15", "type" => "string"],
                    ["value" => "SINGAPORE AIRLINES KRISFLYER 1", "type" => "string"],
                    ["value" => "Award", "type" => "string"],
                    ["value" => "-", "type" => "string"],
                    ["value" => "-2,400", "type" => "miles"],
                ],
            ],
            'miles' => true,
            'total' => 3,
            'extra' => [],
            'balance_cell' => [],
        ];
        $this->assertEquals($expected, $historyForPopup);
    }

    public function testDoubles()
    {
        $this->mockCommunicator('testprovider', 'History.Doubles');
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'History.Doubles');
        $this->aw->checkAccount($accountId, false, true, true);
        $history = $this->db->query("select AccountID, PostingDate, Description, Miles, Info, Position
		from AccountHistory where AccountID = $accountId order by PostingDate DESC, Position ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $expected = [
            [
                'AccountID' => strval($accountId),
                'PostingDate' => '2015-10-27 00:00:00',
                'Description' => 'PURCHASED POINTS-MEMBER SELF 1',
                'Miles' => null,
                'Info' => 'a:1:{s:8:"Activity";s:5:"Bonus";}',
                'Position' => '0',
            ],
            [
                'AccountID' => strval($accountId),
                'PostingDate' => '2015-08-13 00:00:00',
                'Description' => 'PURCHASED POINTS-MEMBER SELF 2',
                'Miles' => null,
                'Info' => 'a:1:{s:8:"Activity";s:5:"Bonus";}',
                'Position' => '1',
            ],
            [
                'AccountID' => strval($accountId),
                'PostingDate' => '2015-08-13 00:00:00',
                'Description' => 'PURCHASED POINTS-MEMBER SELF 2',
                'Miles' => null,
                'Info' => 'a:1:{s:8:"Activity";s:5:"Bonus";}',
                'Position' => '2',
            ],
        ];
        $this->assertEquals($expected, $history);

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $info = $this->container->get('aw.accountinfo');
        $historyForPopup = $info->getAccountHistory($account, 20);
        $expected = [
            'columns' => ['Activity Date', 'Description', 'No.', 'Activity', 'Award Miles'],
            'data' => [
                [
                    ["value" => "10/27/15", "type" => "string"],
                    ["value" => "PURCHASED POINTS-MEMBER SELF 1", "type" => "string"],
                    ["value" => "", "type" => "string"],
                    ["value" => "Bonus", "type" => "string"],
                    ["value" => null, "type" => "miles"],
                ],
                [
                    ["value" => "8/13/15", "type" => "string"],
                    ["value" => "PURCHASED POINTS-MEMBER SELF 2", "type" => "string"],
                    ["value" => "", "type" => "string"],
                    ["value" => "Bonus", "type" => "string"],
                    ["value" => null, "type" => "miles"],
                ],
                [
                    ["value" => "8/13/15", "type" => "string"],
                    ["value" => "PURCHASED POINTS-MEMBER SELF 2", "type" => "string"],
                    ["value" => "", "type" => "string"],
                    ["value" => "Bonus", "type" => "string"],
                    ["value" => null, "type" => "miles"],
                ],
            ],
            'miles' => true,
            'total' => 3,
            'extra' => [],
            'balance_cell' => [],
        ];
        $this->assertEquals($expected, $historyForPopup);
    }

    public function testFieldTypes()
    {
        $providerCode = StringUtils::getRandomCode(20);
        $this->mockCommunicator($providerCode, 'History.FieldTypes');
        $providerId = $this->aw->createAwProvider(StringUtils::getRandomCode(20), $providerCode, [], [
            "GetHistoryColumns" => function () {
                return [
                    "Post Date" => "PostingDate",
                    "Description" => "Description",

                    "Starpoints" => "Miles",
                    "Miles Balance" => "MilesBalance",

                    "Type" => "Info",
                    "Eligible Nights" => "Info",

                    "Amount" => "Amount",
                    "Amount Balance" => "AmountBalance",

                    "Currency" => "Currency",
                    "Category" => "Category",
                    "Bonus" => "Bonus",
                ];
            },

            "ParseHistory" => function ($startDate = null) {
                return [
                    [
                        'Post Date' => 1445904000,
                        'Description' => 'PURCHASED POINTS-MEMBER SELF',

                        'Starpoints' => 100,
                        'Miles Balance' => 200,

                        'Type' => 'Bonus',
                        'Eligible Nights' => '-',

                        'Currency' => 'USD',
                        'Category' => 'Airlines',
                        'Bonus' => '+2,500',
                    ],
                ];
            },
        ]);

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), $providerId, 'History.FieldTypes');
        $this->aw->checkAccount($accountId, false, true, true);
        $history = $this->db->query("select AccountID, PostingDate, Description, Miles, Info, Position
   		from AccountHistory where AccountID = $accountId")->fetchAll(\PDO::FETCH_ASSOC);
        $expected = [
            [
                'AccountID' => strval($accountId),
                'PostingDate' => '2015-10-27 00:00:00',
                'Description' => 'PURCHASED POINTS-MEMBER SELF',
                'Miles' => '100',
                'Info' => 'a:3:{s:4:"Type";s:5:"Bonus";s:15:"Eligible Nights";s:1:"-";s:5:"Bonus";s:6:"+2,500";}',
                'Position' => '0',
            ],
        ];
        $this->assertEquals($expected, $history);
    }

    private function mockCommunicator($provider, $login)
    {
        $columns = [];

        switch ($login) {
            case 'History.FieldTypes':
                $columns = [
                    'Post Date' => 'PostingDate',
                    'Description' => 'Description',
                    'Starpoints' => 'Miles',
                    'Miles Balance' => 'MilesBalance',
                    'Type' => 'Info',
                    'Eligible Nights' => 'Info',
                    'Amount' => 'Amount',
                    'Amount Balance' => 'AmountBalance',
                    'Currency' => 'Currency',
                    'Category' => 'Category',
                    'Bonus' => 'Bonus',
                ];

                break;

            case 'History.Bonus':
                $columns = [
                    'Type' => 'Info',
                    'Eligible Nights' => 'Info',
                    'Post Date' => 'PostingDate',
                    'Description' => 'Description',
                    'Starpoints' => 'Miles',
                    'Bonus' => 'Bonus',
                ];

                break;

            case 'History.Doubles':
                $columns = [
                    'Activity Date' => 'PostingDate',
                    'Activity' => 'Info',
                    'Description' => 'Description',
                    'Award Miles' => 'Miles',
                ];

                break;

            case 'history':
                $columns = [
                    'No.' => 'Info',
                    'Activity Date' => 'PostingDate',
                    'Activity' => 'Info',
                    'Description' => 'Description',
                    'Award Miles' => 'Miles',
                ];

                break;
        }

        $engine = $this->make(Local::class, [
            'getProviderInfo' => Stub::atLeastOnce(function ($code) use ($provider, $columns) {
                $this->assertEquals($provider, $code);
                $historyColumns = [];

                foreach ($columns as $name => $columnCode) {
                    $historyColumns[] = new HistoryColumn($name, $columnCode);
                }

                return (new ProviderInfoResponse())->setHistorycolumns($historyColumns);
            }),
        ]);

        $processor = new HistoryProcessor(
            $this->container->get(LoggerInterface::class),
            $this->container->get('database_connection'),
            $this->container->get(MerchantMatcher::class),
            $this->container->get(ShoppingCategoryMatcher::class),
            $this->container->get(Updater::class),
            $engine,
            $this->container->get(AccountRepository::class)
        );
        $this->mockService(HistoryProcessor::class, $processor);
    }
}
