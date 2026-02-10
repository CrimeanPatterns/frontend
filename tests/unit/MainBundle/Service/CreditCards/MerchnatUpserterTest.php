<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\AccountHistoryRow;
use AwardWallet\MainBundle\Command\CreditCards\CompleteTransactionsCommand\CalculatedMerchantData;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\PostponedMerchantUpdate;
use AwardWallet\MainBundle\Service\CreditCards\MerchantUpserter;
use AwardWallet\MainBundle\Service\CreditCards\Query\ExistingMerchantsQuery;
use AwardWallet\MainBundle\Service\CreditCards\Query\UpsertNewMerchantsQuery;
use AwardWallet\Tests\Unit\BaseTest;
use Clock\ClockTest;
use Doctrine\DBAL\Connection;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class MerchnatUpserterTest extends BaseTest
{
    public function testNoUpsertsForExistingMerchants()
    {
        $upsertQuery = $this->prophesize(UpsertNewMerchantsQuery::class)
            ->execute(Argument::cetera())
            ->shouldNotBeCalled()
            ->getObjectProphecy();
        $existingQuery = $this->prophesize(ExistingMerchantsQuery::class)
            ->execute([
                ['PEPSI', 100],
                ['SPRITE', 200],
            ])
            ->shouldBeCalledOnce()
            ->willReturn([
                ['PEPSI', 1, 100],
                ['SPRITE', 2, 200],
            ])
            ->getObjectProphecy();
        $upserter = new MerchantUpserter(
            $existingQuery->reveal(),
            $upsertQuery->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new ClockTest(),
            $this->mockSphinxConnection()
        );
        [$rowsMap, $assertUpdates] = $this->dataGen();
        $upserter->upsert($rowsMap);
        $assertUpdates();
    }

    public function testFullUpsert()
    {
        $upsertQuery = $this->prophesize(UpsertNewMerchantsQuery::class)
            ->execute([
                'PEPSI',
                'PEPSI',
                100,
                101,

                'SPRITE',
                'SPRITE',
                200,
                201,
            ])
            ->shouldBeCalled()
            ->getObjectProphecy();
        $existingQuery = $this->prophesize(ExistingMerchantsQuery::class)
            ->execute([
                ['PEPSI', 100],
                ['SPRITE', 200],
            ])
            ->willReturn(
                [],
                [
                    ['PEPSI', 1, 100],
                    ['SPRITE', 2, 200],
                ],
            )
            ->getObjectProphecy();
        $upserter = new MerchantUpserter(
            $existingQuery->reveal(),
            $upsertQuery->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new ClockTest(),
            $this->mockSphinxConnection()
        );
        [$rowsMap, $assertUpdates] = $this->dataGen();
        $upserter->upsert($rowsMap);
        $assertUpdates();
    }

    /**
     * @return array{AccountHistoryRow[], callable} test input and asserter
     */
    protected function dataGen(): array
    {
        $historyRowsCount = 5;
        $merchantData = [
            new PostponedMerchantUpdate(
                'pepsi_100',
                'PEPSI',
                'PEPSI',
                100,
                101
            ),
            new PostponedMerchantUpdate(
                'sprite_200',
                'SPRITE',
                'SPRITE',
                200,
                201
            ),
        ];

        $rowsMap = it($merchantData)
            ->reindex(fn (PostponedMerchantUpdate $merchantUpdate) => $merchantUpdate->cacheKey)
            ->map(fn (PostponedMerchantUpdate $merchantUpdate) =>
                it(\iter\range(1, $historyRowsCount))
                ->map(function (int $num) use ($merchantUpdate) {
                    $historyRow = new AccountHistoryRow();
                    $historyRow->CalculatedMerchantData = new CalculatedMerchantData(
                        $num,
                        $merchantUpdate,
                        4
                    );

                    return $historyRow;
                })
                ->toArray()
            )
            ->toArrayWithKeys();

        $asserter = fn () => $this->assertEquals(
            [
                'pepsi_100' => \array_fill(0, $historyRowsCount, 1),
                'sprite_200' => \array_fill(0, $historyRowsCount, 2),
            ],
            it($rowsMap)
            ->map(fn (array $historyRows) =>
                it($historyRows)
                ->map(fn (AccountHistoryRow $row) => $row->CalculatedMerchantData->merchantId)
                ->toArray()
            )
            ->toArrayWithKeys(),
            'history rows was not updated'
        );

        return [$rowsMap, $asserter];
    }

    private function mockSphinxConnection(): Connection
    {
        return $this->prophesize(Connection::class)
            ->reveal();
    }
}
