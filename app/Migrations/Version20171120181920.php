<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171120181920 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `RememberMeToken` ADD `RememberMeTokenID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`RememberMeTokenID`);');
        $this->addSql('ALTER TABLE `Session` ADD `RememberMeTokenID` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `UserID`,
            add foreign key(RememberMeTokenID) references RememberMeToken(RememberMeTokenID) on delete set null;');
        $this->addSql('ALTER TABLE `Session` ADD INDEX(`UserID`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `RememberMeToken` DROP `RememberMeTokenID`');
        $this->addSql('ALTER TABLE `Session` DROP `RememberMeTokenID`');
    }
}
