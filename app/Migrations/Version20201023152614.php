<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201023152614 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `MobileDevice` ADD `Tracked` INT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `Alias`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` DROP `Tracked`');
    }
}
