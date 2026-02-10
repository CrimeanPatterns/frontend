<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Updater;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\HistoryProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryColumn;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use Codeception\Module\CustomDb;
use JMS\Serializer\Serializer;
use Symfony\Bridge\Monolog\Logger;

/**
 * @group frontend-unit
 */
class LoyaltyHistorySavingTest extends BaseContainerTest
{
    /** @var Serializer */
    protected $serializer;
    protected $userId;
    protected $accountId;
    protected $merchantId;
    protected $categoryId;

    public function _before()
    {
        parent::_before();
        $this->serializer = $this->container->get('jms_serializer');
        $this->userId = $this->aw->createAwUser();
        $this->accountId = $this->aw->createAwAccount($this->userId, 'testprovider', 'History.BankTransactions');
        $this->merchantId = $this->db->haveInDatabase('Merchant', ["Name" => "TestMerchant" . bin2hex(random_bytes(10))]);
        $this->categoryId = $this->db->haveInDatabase('ShoppingCategory', ["Name" => "TestCategory" . bin2hex(random_bytes(10)), "MatchingOrder" => 1]);
    }

    public function _after()
    {
        $this->serializer = null;
        $this->userId = null;
        $this->accountId = null;
        $this->db->executeQuery("delete from Merchant where MerchantID = " . $this->merchantId);
        $this->db->executeQuery("delete from ShoppingCategory where ShoppingCategoryID = " . $this->categoryId);
        parent::_after();
    }

    /**
     * @group testGoneAway
     */
    public function testCompleteHistorySaving()
    {
        $merchantMatcher = $this->getMockBuilder(MerchantMatcher::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $merchantMatcher->expects($this->any())
                        ->method('identify')
                        ->willReturn($this->merchantId);
        $categoryMatcher = $this->getMockBuilder(ShoppingCategoryMatcher::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $categoryMatcher->expects($this->any())
                        ->method('identify')
                        ->willReturn($this->categoryId);

        $processor = $this->getHistoryProcessor($merchantMatcher, $categoryMatcher);
        /** @var History $history */
        $history = $this->serializer->deserialize(file_get_contents(__DIR__ . "/../_data/loyaltyHistoryComplete.json"), History::class, 'json');

        /* creating fake subaccounts */
        $subAccHistory = $history->getSubAccounts();
        $subAccs = [];

        if (!empty($subAccHistory)) {
            foreach ($subAccHistory as $subAccHistoryItem) {
                $subAccs[$subAccHistoryItem->getCode()] = $this->aw->createAwSubAccount($this->accountId, ['Code' => $subAccHistoryItem->getCode()]);
            }
        }

        $processor->saveAccountHistory($this->accountId, $history);

        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $results = $I->query('SELECT * FROM AccountHistory WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->rowCount();
        $this->assertEquals(4, $results);
        $accountRow = $I->query('SELECT HistoryState FROM Account WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->fetch();
        $this->assertEquals($accountRow['HistoryState'], $history->getState());

        // incremental сценарий
        /** @var History $history */
        $historyInc = $this->serializer->deserialize(file_get_contents(__DIR__ . "/../_data/loyaltyHistoryIncremental.json"), History::class, 'json');
        $processor = $this->getHistoryProcessor($merchantMatcher, $categoryMatcher);
        $processor->saveAccountHistory($this->accountId, $historyInc);
        $results = $I->query('SELECT * FROM AccountHistory WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->rowCount();
        $this->assertEquals(7, $results);
        $accountRow = $I->query('SELECT HistoryState FROM Account WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->fetch();
        $this->assertEquals($accountRow['HistoryState'], $historyInc->getState());
    }

    /**
     * @group testGoneAway
     */
    public function testTheSameTransactions()
    {
        $processor = $this->getHistoryProcessor();
        /** @var History $history */
        $history = $this->serializer->deserialize(file_get_contents(__DIR__ . "/../_data/loyaltyHistoryComplete.json"), History::class, 'json');
        $history->setSubAccounts(null);

        $processor->saveAccountHistory($this->accountId, $history);
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $results = $I->query('SELECT * FROM AccountHistory WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->rowCount();
        $this->assertEquals(2, $results);
        $accountRow = $I->query('SELECT HistoryState FROM Account WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->fetch();
        $this->assertEquals($accountRow['HistoryState'], $history->getState());

        // Saving new rows with the same data
        /** @var History $history */
        $history = $this->serializer->deserialize(file_get_contents(__DIR__ . "/../_data/loyaltyHistoryTheSame.json"), History::class, 'json');
        $processor = $this->getHistoryProcessor();
        $processor->saveAccountHistory($this->accountId, $history);
        $results = $I->query('SELECT * FROM AccountHistory WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->rowCount();
        $this->assertEquals(3, $results);
        $accountRow = $I->query('SELECT HistoryState FROM Account WHERE AccountID = :AccountID', [':AccountID' => $this->accountId])->fetch();
        $this->assertEquals($accountRow['HistoryState'], $history->getState());
    }

    private function getHistoryProcessor($merchantMatcher = null, $categoryMatcher = null)
    {
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $merchantMatcher = $merchantMatcher ?? $this->getMockBuilder(MerchantMatcher::class)->disableOriginalConstructor()->getMock();
        $categoryMatcher = $categoryMatcher ?? $this->getMockBuilder(ShoppingCategoryMatcher::class)->disableOriginalConstructor()->getMock();
        $planLinkUpdater = $this->getMockBuilder(Updater::class)->disableOriginalConstructor()->getMock();
        $connection = $this->container->get('database_connection');
        $accountRepo = $this->container->get(AccountRepository::class);

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

        $historyColumns = [];

        foreach ($columns as $name => $code) {
            $historyColumns[] = new HistoryColumn($name, $code);
        }

        $engine = $this->getMockBuilder(UpdaterEngineInterface::class)->disableOriginalConstructor()->getMock();
        $engine->expects($this->once())
                     ->method('getProviderInfo')
                     ->with('testprovider')
                     ->willReturn((new ProviderInfoResponse())->setHistorycolumns($historyColumns));

        return new HistoryProcessor($logger, $connection, $merchantMatcher, $categoryMatcher, $planLinkUpdater, $engine, $accountRepo);
    }
}
