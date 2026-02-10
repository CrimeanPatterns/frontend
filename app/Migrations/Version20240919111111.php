<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;
use AwardWallet\MainBundle\Service\MileValue\UserPointValueService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

final class Version20240919111111 extends AbstractMigration implements ContainerAwareMigrationInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
        $userPointValueService = $this->container->get(UserPointValueService::class);

        $aaCondition = sprintf('(a.ProviderID IS NULL AND %s)', AmericanAirlinesAAdvantageDetector::getSQLFilter('a'));

        $accounts = $this->connection->fetchAllAssociative('
            SELECT a.UserID, AccountID, PointValue, up.Value AS ProviderValue
            FROM Account a
            LEFT JOIN UserPointValue up ON (up.UserID = a.UserID AND up.ProviderID = ' . Provider::AA_ID . ') 
            WHERE
                    ' . $aaCondition . '
                AND a.PointValue IS NOT NULL
        ');

        $groups = [];

        foreach ($accounts as $account) {
            $userId = (int) $account['UserID'];

            if (!array_key_exists($userId, $groups)) {
                $groups[$userId] = [];
            }

            $groups[$userId][] = $account;
        }

        $accounts = null;
        $updateDate = date('Y-m-d 00:00:00');
        $this->write(PHP_EOL);

        foreach ($groups as $userId => $accounts) {
            $account = $accounts[0];

            if (empty($account['ProviderValue'])) {
                $this->connection->executeQuery('
                    INSERT INTO UserPointValue (UserID, ProviderID, Value, UpdateDate)
                    VALUES (?, ?, ?, ?)
                ', [
                    $userId,
                    Provider::AA_ID,
                    $account['PointValue'],
                    $updateDate,
                ],
                    [
                        \PDO::PARAM_INT,
                        \PDO::PARAM_INT,
                        \PDO::PARAM_STR,
                        \PDO::PARAM_STR,
                    ]
                );
            }

            $this->connection->executeQuery('
                UPDATE Account
                SET PointValue = NULL
                WHERE
                        UserID = ' . $userId . '
                    AND AccountID IN (' . implode(',', array_column($accounts, 'AccountID')) . ')
            ');

            foreach ($accounts as $acc) {
                $userPointValueService->invalidateCache([
                    'ProviderID' => Provider::AA_ID,
                    'AccountID' => $acc['AccountID'],
                ]);
            }

            $this->write(\json_encode($accounts) . PHP_EOL);
        }

        $this->write(PHP_EOL);
    }

    public function down(Schema $schema): void
    {
    }
}
