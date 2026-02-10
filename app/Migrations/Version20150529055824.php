<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150529055824 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Review` ADD CONSTRAINT `fk_review_ref_provider_` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `Review` DROP FOREIGN KEY `fk_review_ref_provider`");
    }

    public function down(Schema $schema): void
    {
    }
}
