<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180123092726 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ProviderCoupon ADD COLUMN AccountID INT(11) DEFAULT NULL, ADD CONSTRAINT fk_ProviderCoupon_ref_Account FOREIGN KEY (AccountID) REFERENCES Account (AccountID) ON DELETE SET NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ProviderCoupon DROP FOREIGN KEY fk_ProviderCoupon_ref_Account, DROP COLUMN AccountID');
    }
}
