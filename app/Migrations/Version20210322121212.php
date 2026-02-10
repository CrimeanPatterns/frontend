<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20210322121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `UserCreditCard` SET EarliestSeenDate = LastSeenDate WHERE EarliestSeenDate IS NOT NULL AND LastSeenDate IS NOT NULL AND EarliestSeenDate > LastSeenDate');
        $this->addSql("ALTER TABLE `UserCreditCard` ADD `ClosedDate` DATE NULL DEFAULT NULL COMMENT 'Ориентировочная дата закрытия, когда перестали обнаруживать'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `UserCreditCard` DROP `ClosedDate`');
    }
}
