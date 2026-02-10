<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220210101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Aircraft` ADD `IcaoCode` VARCHAR(4) NULL DEFAULT NULL AFTER `IataCode`');
        $this->addSql('ALTER TABLE `Aircraft` ADD INDEX(`IcaoCode`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Aircraft` DROP INDEX `IcaoCode`');
        $this->addSql('ALTER TABLE `Aircraft` DROP `IcaoCode`');
    }
}
