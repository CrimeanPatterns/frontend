<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141107100525 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `Birthday` `Birthday` DATETIME  NULL  COMMENT 'Дата рождения';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbPassenger` CHANGE `Birthday` `Birthday` DATETIME  NOT NULL  COMMENT 'Дата рождения';");
    }
}
