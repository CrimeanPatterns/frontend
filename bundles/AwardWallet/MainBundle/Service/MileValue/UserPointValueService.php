<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountChangedEvent;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\ProviderHandler;
use Doctrine\DBAL\Connection;

class UserPointValueService
{
    private const CACHE_KEY_PROVIDERS = 'userPointValue_data_providers_user_%d';
    private const CACHE_KEY_ACCOUNTS = 'userPointValue_data_accounts_user_%d_v2';
    private const CACHE_LIFETIME = 86400 * 7;

    private Connection $connection;
    private CacheManager $cacheManager;
    private MileValueHandler $mileValueHandler;
    private ProviderHandler $providerHandler;

    public function __construct(
        Connection $connection,
        CacheManager $cacheManager,
        MileValueHandler $mileValueHandler,
        ProviderHandler $providerHandler
    ) {
        $this->connection = $connection;
        $this->cacheManager = $cacheManager;
        $this->mileValueHandler = $mileValueHandler;
        $this->providerHandler = $providerHandler;
    }

    public function getUserSetValues(Usr $user): array
    {
        $userId = $user->getId();
        $cacheKey = $this->getProviderCacheKey($userId);

        $cacheReference = new CacheItemReference(
            $cacheKey,
            [self::CACHE_KEY_PROVIDERS],
            function () use ($userId) {
                $userValuesQuery = $this->connection->prepare('
                    SELECT
                            upv.ProviderID, upv.Value AS AvgUserValue,
                            p.DisplayName, p.Kind, p.Code
                    FROM UserPointValue upv
                    JOIN Provider p ON (upv.ProviderID = p.ProviderID)
                    WHERE
                            upv.UserID = :userId
                        AND (
                                p.State <> ' . PROVIDER_DISABLED . '
                            OR  upv.ProviderID = ' . Provider::AA_ID . '
                        )
                ');
                $userValuesQuery->bindParam(':userId', $userId, \PDO::PARAM_INT);
                $userValuesQuery->execute();
                $rows = $userValuesQuery->fetchAll();

                $result = [];

                foreach ($rows as $row) {
                    $providerId = $row['ProviderID'];
                    $result[$providerId] = [
                        'ProviderID' => $providerId,
                        'Code' => $row['Code'],
                        'DisplayName' => $row['DisplayName'],
                        'Kind' => $row['Kind'],
                    ];
                    $result[$providerId]['user'] = $this->mileValueHandler->formatter(
                        MileValueService::PRIMARY_CALC_FIELD,
                        [MileValueService::PRIMARY_CALC_FIELD => $row['AvgUserValue']],
                        true
                    );
                }

                return $result;
            }
        );
        $cacheReference->setExpiration(self::CACHE_LIFETIME);

        return $this->cacheManager->load($cacheReference);
    }

    public function getAccountsUserSetValues($user): array
    {
        $userId = $user instanceof Usr ? $user->getId() : (int) $user;
        $cacheKey = $this->getAccountCacheKey($userId);

        $cacheReference = new CacheItemReference(
            $cacheKey,
            [self::CACHE_KEY_ACCOUNTS],
            function () use ($userId) {
                $userValuesQuery = $this->connection->prepare('
                    SELECT
                            a.AccountID, a.PointValue AS AvgUserValue, a.Login,
                            a.ProgramName, a.Kind
                    FROM Account a
                    WHERE
                            a.UserID = :userId
                        AND a.PointValue IS NOT NULL
                ');
                $userValuesQuery->bindParam(':userId', $userId, \PDO::PARAM_INT);
                $userValuesQuery->execute();
                $rows = $userValuesQuery->fetchAll();

                $result = [];

                foreach ($rows as $row) {
                    $accountId = $row['AccountID'];
                    $result[$accountId] = [
                        'AccountID' => $accountId,
                        'DisplayName' => $row['ProgramName'],
                        'Kind' => $row['Kind'],
                        'Login' => $row['Login'],
                    ];
                    $result[$accountId]['user'] = $this->mileValueHandler->formatter(
                        MileValueService::PRIMARY_CALC_FIELD,
                        [MileValueService::PRIMARY_CALC_FIELD => $row['AvgUserValue']],
                        true
                    );
                }

                return $result;
            }
        );
        $cacheReference->setExpiration(self::CACHE_LIFETIME);

        return $this->cacheManager->load($cacheReference);
    }

    public function setProviderUserPointValue(int $userId, int $providerId, float $value): bool
    {
        return $this->createUserPointValue([
            'UserID' => $userId,
            'ProviderID' => $providerId,
            'Value' => $value,
        ]);
    }

    public function setAccountUserPointValue(Account $account, float $value): bool
    {
        $fields = ['UserID' => $account->getUser()->getId(), 'AccountID' => $account->getId()];
        $affected = $this->connection->update(
            'Account',
            ['PointValue' => $value],
            $fields,
            ['PointValue' => \PDO::PARAM_STR]
        );
        $this->invalidateCache($fields);

        return $affected > 0;
    }

    public function removeProviderUserPointValue(int $userId, int $providerId): bool
    {
        return $this->removeUserPointValue([
            'UserID' => $userId,
            'ProviderID' => $providerId,
        ]);
    }

    public function assignUserPointKinds(array $data, array $accountPoints)
    {
        foreach ($accountPoints as $account) {
            $accountKind = $account['Kind'];
            $kindKey = ProviderHandler::KIND_KEYS[$accountKind];

            if (array_key_exists($kindKey, $data)) {
                $data[$kindKey]['data'][] = $account;
            } else {
                $data[$kindKey] = [
                    'title' => $this->providerHandler->getLocalizedKind($account['Kind']),
                    'data' => [$account],
                ];
            }
        }

        return $data;
    }

    public function removeAccountUserPointValue(Account $account): bool
    {
        $fields = ['UserID' => $account->getUser()->getId(), 'AccountID' => $account->getId()];
        $affected = $this->connection->update(
            'Account',
            ['PointValue' => null],
            $fields,
            ['PointValue' => \PDO::PARAM_NULL]
        );
        $this->invalidateCache($fields);

        return $affected > 0;
    }

    public function onAccountChanged(AccountChangedEvent $event): void
    {
        $this->invalidateCache(['AccountID' => $event->getAccountId()]);
    }

    public function invalidateCache(array $fields): void
    {
        if (array_key_exists('ProviderID', $fields)) {
            $this->cacheManager->invalidateTags([self::CACHE_KEY_PROVIDERS], false);
        }

        if (array_key_exists('AccountID', $fields)) {
            $this->cacheManager->invalidateTags([self::CACHE_KEY_ACCOUNTS], false);
        }
    }

    private function createUserPointValue(array $fields): bool
    {
        $fieldsName = implode(',', array_keys($fields));
        $fieldsCountValues = implode(',', array_fill(0, count($fields), '?'));
        $values = array_merge(array_values($fields), [$fields['Value']]);
        $types = [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR];

        $result = (bool) $this->connection->executeStatement('
            INSERT INTO
                UserPointValue (' . $fieldsName . ') VALUES (' . $fieldsCountValues . ')
            ON DUPLICATE KEY
                UPDATE Value = ?',
            $values,
            $types
        );
        $this->invalidateCache($fields);

        return $result;
    }

    private function removeUserPointValue(array $fields): bool
    {
        $result = (bool) $this->connection->delete('UserPointValue', $fields, [\PDO::PARAM_INT, \PDO::PARAM_INT]);
        $this->invalidateCache($fields);

        return $result;
    }

    private function getProviderCacheKey(int $userId): string
    {
        return sprintf(self::CACHE_KEY_PROVIDERS, $userId);
    }

    private function getAccountCacheKey(int $userId): string
    {
        return sprintf(self::CACHE_KEY_ACCOUNTS, $userId);
    }
}
