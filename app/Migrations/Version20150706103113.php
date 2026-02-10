<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150706103113 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE OfferUser ADD KEY `idx_OfferID_Agreed_key` (`OfferID`, `Agreed`)');
        $this->addSql('ALTER TABLE OfferLog ADD KEY  `idx_OfferID_Action_UserID_key` (`OfferID`, `Action`, `UserID`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE OfferLog DROP KEY `idx_OfferID_Action_UserID_key`');
        $this->addSql('ALTER TABLE OfferUser DROP KEY `idx_OfferID_Agreed_key`');
    }
}
