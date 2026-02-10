<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131204033816 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ArrDateFrom` `ReturnDateFrom` DATETIME  NULL  DEFAULT NULL  COMMENT 'Дата возвращения обратно, начальная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ArrDateTo` `ReturnDateTo` DATETIME  NULL  DEFAULT NULL  COMMENT 'Дата возвращения обратно, конечная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ArrDateIdeal` `ReturnDateIdeal` DATETIME  NULL  DEFAULT NULL  COMMENT 'Дата прибытия идеальная';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ReturnDateFrom` `ArrDateFrom` DATETIME  NULL  DEFAULT NULL  COMMENT 'Дата возвращения обратно, начальная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ReturnDateTo` `ArrDateTo` DATETIME  NULL  DEFAULT NULL  COMMENT 'Дата возвращения обратно, конечная';");
        $this->addSql("ALTER TABLE `AbSegment` CHANGE `ReturnDateIdeal` `ArrDateIdeal` DATETIME  NULL  DEFAULT NULL  COMMENT 'Дата прибытия идеальная';");
    }
}
