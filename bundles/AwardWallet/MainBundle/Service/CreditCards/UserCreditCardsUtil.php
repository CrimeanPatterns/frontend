<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

class UserCreditCardsUtil
{
    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    public function filterAccounts(array $accounts): array
    {
        $accountIds = array_column($accounts, 'AccountID');

        if (empty($accountIds)) {
            return $accounts;
        }

        $accountIds = array_unique($accountIds);
        $usAccounts = $this->connection->executeQuery("
            SELECT AccountID
            FROM Account
            WHERE
                    AccountID IN (" . implode(',', $accountIds) . ")
                -- filter Non US Accounts
                AND (
                       Login2 LIKE 'US'
                    OR Login2 LIKE 'USA'
                    OR Login2 LIKE 'United States'
                    OR Login2 LIKE 'United states of America'
                    OR Login2 LIKE 'en-US'
                    OR Login2 = ''
                    OR Login2 IS NULL
                    OR Login2 LIKE 'english'
                    OR Login2 LIKE 'Select'
                    OR Login2 LIKE 'America'
                )
                -- filter FamilyMember Accounts
                AND UserAgentID IS NULL
            ")->fetchAll(FetchMode::COLUMN);

        foreach ($accounts as $key => $account) {
            if (array_key_exists('AccountID', $account) && !in_array($account['AccountID'], $usAccounts)) {
                unset($accounts[$key]);
            }
        }

        return array_values($accounts);
    }
}
