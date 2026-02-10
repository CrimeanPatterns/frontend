<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221026061950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlight` ADD `IsMixedCabin` TINYINT(1) NOT NULL DEFAULT '0'");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxIsMixedCabin` (`IsMixedCabin`)");

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlight` DROP INDEX `idxIsMixedCabin`");
        $this->addSql("ALTER TABLE `RAFlight` DROP `IsMixedCabin`");

    }
}
