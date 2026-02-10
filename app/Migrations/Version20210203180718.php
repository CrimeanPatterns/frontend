<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210203180718 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if (!$schema->getTable('QsTransaction')->hasIndex('QsTransaction_UserID_idx')) {
            $this->addSql('ALTER TABLE `QsTransaction` ADD INDEX `QsTransaction_UserID_idx` (`UserID`);');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('QsTransaction')->hasIndex('QsTransaction_UserID_idx')) {
            $this->addSql('ALTER TABLE `QsTransaction` DROP INDEX `QsTransaction_UserID_idx`');
        }
    }
}
