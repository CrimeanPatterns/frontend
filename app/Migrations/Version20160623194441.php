<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160623194441 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `UserAgent` ADD `AccessPopupShown` TINYINT  UNSIGNED  NOT NULL  DEFAULT '0'  COMMENT 'Был ли показан попап установки прав доступа' ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `UserAgent` DROP COLUMN `AccessPopupShown`');
    }
}
