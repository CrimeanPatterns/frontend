<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160916100717 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              ADD MpDisableAll TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Отключить все mobile push-уведомления'  AFTER WpRewardsActivity,
              ADD MpPlans TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления для резерваций'  AFTER MpDisableAll,
              ADD MpLoyaltyProgram TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления для программ лояльности'  AFTER MpPlans,
              ADD MpBooking TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Разрешить mobile push-уведомления в букинге'  AFTER MpLoyaltyProgram
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr 
              DROP COLUMN MpDisableAll,
              DROP COLUMN MpPlans,
              DROP COLUMN MpLoyaltyProgram,
              DROP COLUMN MpBooking
        ");
    }
}
