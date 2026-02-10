<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150609124311 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Provider ADD CheckInMobileBrowser TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL COMMENT 'Сайт RetailBenefits, после всех редиректов' AFTER CheckInBrowser");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Provider DROP CheckInMobileBrowser');
    }
}
