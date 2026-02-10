<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190523143923 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `Provider` 
              ADD COLUMN `IgnoreEmailsFromPartners` VARCHAR(100) NOT NULL DEFAULT '' AFTER `CanChangePasswordServer`;
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `Provider` 
DROP COLUMN `IgnoreEmailsFromPartners`;
        ");
    }
}
