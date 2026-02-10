<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\AccountHistoryRow;
use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\CalculatedMerchantData;
use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\CompleteTransactionsCommand;
use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\HistoryRowsProcessor;
use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\MainQuery;
use AwardWallet\MainBundle\Service\LockWrapper;
use AwardWallet\Tests\Unit\CommandTester;
use Clock\ClockTest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

/**
 * @group frontend-unit
 */
class CompleteTransactionsCommandTest extends CommandTester
{
    /**
     * @var CompleteTransactionsCommand
     */
    protected $command;

    public function _before()
    {
        parent::_before();
    }

    public function _after()
    {
        $this->cleanCommand();
        parent::_after();
    }

    public function testBatchUpdateFreshRows()
    {
        $mainQuery = $this->prophesize(MainQuery::class);
        $mainQuery
            ->execute(Argument::cetera())
            ->willReturn(
                it(\range(0, 99))
                ->map(fn (int $id) => create(function (AccountHistoryRow $row) use ($id) {
                    $row->UUID = "id{$id}";
                    $row->Description = "Desc{$id}";
                    $row->Miles = 100;
                    $row->Amount = 100;
                    $row->PostingDate = new \DateTime();
                    $row->Category = "Category{$id}";
                    $row->ShoppingCategoryID = null;
                    $row->MerchantID = null;
                    $row->Multiplier = 2;
                    $row->ProviderID = null;
                    $row->UpdateDate = new \DateTime();
                    $row->CalculatedMerchantData = null;
                }))
                ->toArray(),
            )
            ->getObjectProphecy();
        $historyRowsProcessor =
            $this->prophesize(HistoryRowsProcessor::class)
            ->process(Argument::cetera())
            ->will(function (array $args) {
                /** @var AccountHistoryRow[] $rows */
                $rows = $args[0];

                foreach ($rows as $id => $row) {
                    $row->CalculatedMerchantData = new CalculatedMerchantData(
                        null,
                        100500 + $id,
                        null
                    );
                }

                return (function () use ($rows) {
                    yield from $rows;

                    return [
                        'categoriesMap' => [],
                        'processedHistoryRowsCount' => 1,
                        'upsertedMerchantsCount' => 1,
                        'merchantMatcherStats' => [],
                    ];
                })();
            })
            ->shouldBeCalled()
            ->getObjectProphecy();

        $batchUpdater = $this->prophesize(BatchUpdater::class)
            ->batchUpdate(
                Argument::that(fn (array $rows) =>
                    (\count($rows) === 50)
                    && ($rows[0]['UUID'] === 'id0')
                    && ($rows[49]['UUID'] === 'id49')
                ),
                Argument::cetera(),
            )
            ->willReturn(1)
            ->shouldBeCalled()

            ->getObjectProphecy()

            ->batchUpdate(
                Argument::that(fn (array $rows) =>
                    (\count($rows) === 50)
                    && ($rows[0]['UUID'] === 'id50')
                    && ($rows[49]['UUID'] === 'id99')
                ),
                Argument::cetera(),
            )
            ->willReturn(1)
            ->shouldBeCalled()

            ->getObjectProphecy();

        $this->runCommand(
            $mainQuery->reveal(),
            $historyRowsProcessor->reveal(),
            $batchUpdater->reveal(),
        );
    }

    protected function runCommand(
        MainQuery $mainQuery,
        HistoryRowsProcessor $historyRowsProcessor,
        BatchUpdater $batchUpdater,
        array $params = []
    ): void {
        $noLockWrapper = new class() extends LockWrapper {
            public function __construct()
            {
                parent::__construct(new Factory(new FlockStore()));
            }

            public function wrap(string $key, callable $callable, $ttl = 300)
            {
                return $callable();
            }
        };

        $this->initCommand(
            new CompleteTransactionsCommand(
                $this->container->get(LoggerInterface::class),
                $noLockWrapper,
                $mainQuery,
                $historyRowsProcessor,
                $batchUpdater,
                new ClockTest(),
            )
        );

        $this->clearLogs();
        $this->executeCommand($params);
    }
}
