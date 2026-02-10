<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171126130051 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              ADD MpRetailCards TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления с баркодами в заданных локациях' AFTER MpFamilyMemberAlert
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              DROP COLUMN MpRetailCards
        ");
    }
}
