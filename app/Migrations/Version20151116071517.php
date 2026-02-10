<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151116071517 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` ADD `EmailExpiration` TINYINT  UNSIGNED  NOT NULL  DEFAULT \'1\'  COMMENT \'Отправлять ли письма о протухании балансов\'  AFTER `EmailBooking`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` DROP `EmailExpiration`');
    }
}
