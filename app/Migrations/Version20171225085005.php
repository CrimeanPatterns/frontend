<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171225085005 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage`
                add column `CCDetected` int(1) not null default 0 comment 'карточка определена как кредитная карта',
                add column `CCDetectorVersion` varchar(64) default null comment 'версия детектора'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage`
                drop column `CCDetected`,
                drop column `CCDetectorVersion`    
        ");
    }
}
