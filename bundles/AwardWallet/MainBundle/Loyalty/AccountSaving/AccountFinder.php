<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class AccountFinder
{
    private Connection $connection;

    private UseragentRepository $useragentRepository;

    private AccountRepository $accountRepository;

    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        UseragentRepository $useragentRepository,
        AccountRepository $accountRepository,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->useragentRepository = $useragentRepository;
        $this->accountRepository = $accountRepository;
        $this->logger = $logger;
    }

    public function findAccountByAccountNumber(Usr $user, string $providerCode, string $accountNumber): ?Account
    {
        $this->useragentRepository->setAgentFilters($user->getId());
        $accountId = $this->connection->executeQuery("
                SELECT
                    a.AccountID
                FROM
                    Account a
                    JOIN Provider p ON a.ProviderID = p.ProviderID
                    LEFT OUTER JOIN AccountProperty ap ON a.AccountID = ap.AccountID
                    LEFT OUTER JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID
                WHERE
                    ({$this->useragentRepository->userAgentAccountFilter})
                    AND p.Code = :code
                    AND (
                        a.Login = :number
                        OR (ap.Val = :number AND pp.Kind = :kind)
                    )
            ", [
            'code' => $providerCode,
            'number' => $accountNumber,
            'kind' => PROPERTY_KIND_NUMBER,
        ])->fetchOne();

        if ($accountId === false) {
            return null;
        }

        return $this->accountRepository->find($accountId);
    }

    public function findAccountByOwner(Account $account, string $providerCode, ?string $ownerName): ?Account
    {
        $user = $account->getUser();
        $this->useragentRepository->setAgentFilters($user->getId());
        $otherAccounts = (int) $this->connection->executeQuery("
            SELECT 
                COUNT(*)
            FROM
                Account a
                JOIN Provider p ON a.ProviderID = p.ProviderID
            WHERE
                ({$this->useragentRepository->userAgentAccountFilter})
                AND p.Code = :code
                AND a.AccountID <> :account
        ", [
            'code' => $account->getProviderid()->getCode(),
            'account' => $account->getId(),
        ])->fetchOne();

        $accounts = $this->connection->executeQuery("
            SELECT
                a.AccountID,
                IF(a.UserAgentID IS NOT NULL, 'UA', 'U') AS Prefix,
                COALESCE(a.UserAgentID, a.UserID) AS ID,
                LOWER(COALESCE(TRIM(CONCAT(TRIM(CONCAT(ua.FirstName, ' ', COALESCE(ua.MidName, ''))), ' ', ua.LastName)), IF(u.AccountLevel <> :accountLevel, CONCAT(TRIM(CONCAT(u.FirstName, ' ', COALESCE(u.MidName, ''))), ' ', u.LastName), NULL))) AS UserName
            FROM
                Account a
                JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN Usr u ON a.UserID = u.UserID
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
            WHERE
                ({$this->useragentRepository->userAgentAccountFilter})
                AND p.Code = :code
        ", [
            'code' => $providerCode,
            'accountLevel' => ACCOUNT_LEVEL_BUSINESS,
        ])->fetchAllAssociative();

        if (count($accounts) === 0) {
            $this->logger->info(sprintf('accounts was not found, provider code: %s, owner: %s', $providerCode, $ownerName));

            return null;
        }

        $groups = [];
        $matched = [];
        $preparedOwnerName = mb_strtolower($ownerName);

        foreach ($accounts as $row) {
            $id = sprintf('%s%d', $row['Prefix'], $row['ID']);

            if (!isset($groups[$id])) {
                $groups[$id] = [];
            }

            if (!empty($preparedOwnerName) && $this->userNamesAreEqual($row['UserName'], $preparedOwnerName)) {
                $matched[$id] = true;
            }

            $groups[$id][] = $row['AccountID'];
        }

        $matched = array_filter(array_keys($matched), function (string $key) use ($groups) {
            return isset($groups[$key]) && count($groups[$key]) === 1;
        });

        $this->logger->info(sprintf(
            'matched %s, provider code: %s, owner: %s, other accounts: %d',
            json_encode($matched),
            $providerCode,
            $ownerName,
            $otherAccounts
        ));

        if (count($matched) === 1) {
            $this->logger->info('one account was found, by user name');

            return $this->accountRepository->find($groups[$matched[0]][0]);
        }

        if ($otherAccounts === 0 && count($accounts) === 1) {
            $this->logger->info('one account was found, by provider');

            return $this->accountRepository->find($accounts[0]['AccountID']);
        }

        return null;
    }

    private function userNamesAreEqual(?string $name1, ?string $name2): bool
    {
        if (is_null($name1) || is_null($name2)) {
            return false;
        }

        if ($name1 === $name2) {
            return true;
        }

        $middleNameRemoval = fn (string $name): string => preg_replace('/^(\w+)\s+\w\s+(\w+)$/ims', '$1 $2', $name);
        $name1 = $middleNameRemoval($name1);
        $name2 = $middleNameRemoval($name2);

        return $name1 === $name2;
    }
}
