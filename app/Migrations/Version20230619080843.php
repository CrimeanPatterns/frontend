<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230619080843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'set COLLATE utf8mb4_unicode_ci';
    }

    public function up(Schema $schema): void
    {
//        $tables = ['AccountBalance', 'AccountHistory'];
//
//        foreach ($tables as $table) {
//            if (in_array($table, ['RAFlight', 'MigrationVersions'])) {
//                continue;
//            }
//
//            $this->write('Processing table ' . $table);
//            $this->connection->executeStatement(
//                sprintf(
//                    'ALTER TABLE %s CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
//                    $table
//                )
//            );
//        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
