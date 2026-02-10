<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180323122704 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` ADD INDEX `HistoryDataIndex` (`AccountID`, `SubAccountID`, `PostingDate`, `Position`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` DROP INDEX `HistoryDataIndex`");
    }
}
