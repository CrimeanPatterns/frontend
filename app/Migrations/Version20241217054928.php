<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

final class Version20241217054928 extends AbstractMigration
{
    private const EARLY_SUPPORTERS_DATA_FILE = __DIR__ . '/../../bundles/AwardWallet/MainBundle/Command/UpgradeVIPUsers/early_supporters_2024_11_18.php';
    private const FULL_30_SUPPORTERS_DATA_FILE = __DIR__ . '/../../bundles/AwardWallet/MainBundle/Command/UpgradeVIPUsers/full_30_supporters_2024_11_18.php';
    private const BATCH_INSERT_NUM_ROWS = 1000;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `VIPUsersPreEvaluation` (
            `UserID` INT(11) NOT NULL,
            `EarlySupporter` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Early supporter subscription',
            UNIQUE KEY `UserSubscription20241118-UserID` (`UserID`)
        )");

        /** @var array<int, bool> $chunk */
        foreach (
            it(include self::EARLY_SUPPORTERS_DATA_FILE)
            ->map(fn (int $userId) => [$userId, 1]) // early supporters flag = true
            ->chain(
                it(include self::FULL_30_SUPPORTERS_DATA_FILE)
                ->map(fn (int $userId) => [$userId, 0]) // early supporters flag = false
            )
            ->flatten()
            ->chunk(self::BATCH_INSERT_NUM_ROWS * 2) as $chunkMap // 2 because we have 2 values
        ) {
            $this->addSql(
                'INSERT INTO VIPUsersPreEvaluation (UserID, EarlySupporter) VALUES ' . implode(
                    ',',
                    array_fill(0, count($chunkMap) / 2, '(?, ?)')
                ),
                $chunkMap
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE VIPUsersPreEvaluation');
    }
}
