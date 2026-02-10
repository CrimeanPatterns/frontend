<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\MerchantRecentTransactionsStatLoader;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Service\Cache\Memoizer;
use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\minutes;

class MerchantRecentTransactionsStatQuery
{
    private Connection $clickhouse;
    private ParameterRepository $parameterRepository;
    private Memoizer $memoizer;

    public function __construct(
        Connection $clickhouse,
        ParameterRepository $parameterRepository,
        Memoizer $memoizer
    ) {
        $this->clickhouse = $clickhouse;
        $this->parameterRepository = $parameterRepository;
        $this->memoizer = $memoizer;
    }

    /**
     * @return array<int, Stat[]>
     */
    public function execute(array $merchantIds, int $minMerchantTransactions, \DateTime $startDate): array
    {
        $dbVersion = \sprintf(
            "awardwallet_v%s",
            $this->parameterRepository->getParam(ParameterRepository::CLICKHOUSE_DB_VERSION)
        );
        \sort($merchantIds);

        return it(
            $this->memoizer->memoize(
                'MerchantRecentTransactionsStatQuery_execute',
                minutes(5),
                fn () => $this->doExecute(
                    $dbVersion,
                    $merchantIds,
                    $startDate,
                    $minMerchantTransactions
                ),
                $dbVersion,
                $merchantIds,
                $startDate,
                $minMerchantTransactions
            )
        )
        ->map(static function (array $row) {
            $merchantId = (int) $row[0];

            return [
                $merchantId,
                new Stat(
                    $merchantId,
                    (int) $row[1],
                    (float) $row[2],
                    (int) $row[3]
                ),
            ];
        })
        ->fromPairs()
        ->groupAdjacentByKey()
        ->toArrayWithKeys();
    }

    private function doExecute(string $dbVersion, array $merchantIds, \DateTime $startDate, int $minMerchantTransactions): array
    {
        return $this->clickhouse->executeQuery("
                select 
                    MerchantID,
                    CreditCardID, 
                    Multiplier, 
                    Transactions 
                from (
                    select
                        MerchantID,
                        s.CreditCardID as CreditCardID, 
                        h.Multiplier, 
                        count(*) as Transactions
                    from {$dbVersion}.AccountHistory h 
                        join {$dbVersion}.SubAccount s on h.SubAccountID = s.SubAccountID 
                    where 
                        MerchantID in (" . \implode(',', \array_fill(0, \count($merchantIds), '?')) . ")
                        and h.PostingDate >= ?
                        and s.CreditCardID is not null
                        and h.Amount > 0
                        and toDecimal64(h.Miles, 0) > 0
                    group by 
                        MerchantID,
                        s.CreditCardID, 
                        Multiplier
                ) d
                where Transactions >= ?
                order by 
                    MerchantID, 
                    CreditCardID, 
                    Multiplier DESC",
            \array_merge(
                $merchantIds,
                [
                    $startDate->format('Y-m-d'),
                    $minMerchantTransactions,
                ]
            )
        )
        ->fetchAllNumeric();
    }
}
