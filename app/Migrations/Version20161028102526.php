<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161028102526 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Account` ADD KEY `idxAccountCreationDate` (`CreationDate`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Account` DROP KEY `idxAccountCreationDate`');
    }
}
