<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class PasswordVaultRepository extends EntityRepository
{
    /**
     * @return bool
     */
    public function hasAccess(Account $account, Usr $user)
    {
        return $this->hasAccessScalar($account->getAccountid(), $user->getLogin());
    }

    /**
     * @param int $accountId
     * @param string $login user login
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function hasAccessScalar($accountId, $login)
    {
        return !empty(
            $this->getEntityManager()->getConnection()->executeQuery("
                SELECT 1
                FROM
                    PasswordVault pv
                    JOIN PasswordVaultUser pvu ON pvu.PasswordVaultID = pv.PasswordVaultID
                    JOIN Usr u ON u.UserID = pvu.UserID
                WHERE
                    u.Login = ? AND
                    pv.ExpirationDate > NOW() AND
                    pv.AccountID = ? AND
                    pv.Approved = 1",
                [$login, $accountId],
                [\PDO::PARAM_STR, \PDO::PARAM_INT]
            )->fetchAll()
        );
    }

    /**
     * @param int|int[] $accounts
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAccountsUsers($accounts)
    {
        if (!is_array($accounts)) {
            $accounts = [$accounts];
        }

        $fetchedRows = $this->getEntityManager()->getConnection()->executeQuery("
            SELECT
                pv.AccountID,
                GROUP_CONCAT(DISTINCT u.Login SEPARATOR ',') as Login,
                GROUP_CONCAT(DISTINCT u.UserID SEPARATOR  ',') as UserID
            FROM
                PasswordVault pv
                JOIN PasswordVaultUser pvu ON pvu.PasswordVaultID = pv.PasswordVaultID
                JOIN Usr u ON u.UserID = pvu.UserID
            WHERE
                pv.AccountID IN(?) AND
                pv.ExpirationDate > NOW() AND
                pv.Approved = 1
            GROUP BY 
                pv.AccountID",
            [$accounts],
            [Connection::PARAM_INT_ARRAY]
        )->fetchAll();

        $result = [];

        foreach ($fetchedRows as $fetchedRow) {
            $result[$fetchedRow['AccountID']] = [
                'Login' => explode(',', $fetchedRow['Login']),
                'UserID' => explode(',', $fetchedRow['UserID']),
            ];
        }

        return $result;
    }
}
