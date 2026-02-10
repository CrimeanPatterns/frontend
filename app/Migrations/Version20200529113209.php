<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200529113209 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` ADD INDEX `HistoryDataIndex3` (`MerchantID`, `ShoppingCategoryID`, `PostingDate` DESC);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AccountHistory` DROP INDEX `HistoryDataIndex3`;");
    }
}
