<?php

namespace AwardWallet\MainBundle\Loyalty\HistoryState;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

class HistoryDateFinder
{
    private Statement $query;

    public function __construct(Connection $connection)
    {
        $this->query = $connection->prepare("select sa.Code, max(PostingDate) from AccountHistory h
        left outer join SubAccount sa on h.SubAccountID = sa.SubAccountID
        where h.AccountID = ?
        group by sa.Code");
    }

    /**
     * @return ["SubAccountCode1" => "2020-08-02", "SubAccountCode2" => "2020-08-03", "" => "2020-08-02"], "" means main account
     */
    public function getHistoryDates(int $accountId): ?array
    {
        $result = $this->query->executeQuery([$accountId])->fetchAllKeyValue();

        if ($result === false) {
            return null;
        }

        return $result;
    }
}
