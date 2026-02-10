<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131128111629 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ArrDateFrom` `ArrDateFrom` DATETIME  NULL  COMMENT 'Дата возвращения обратно, начальная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ArrDateTo` `ArrDateTo` DATETIME  NULL  COMMENT 'Дата возвращения обратно, конечная';");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ArrDateFrom` `ArrDateFrom` DATETIME  NOT NULL  COMMENT 'Дата прибытия начальная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ArrDateTo` `ArrDateTo` DATETIME  NOT NULL  COMMENT 'Дата прибытия конечная';");
    }
}
