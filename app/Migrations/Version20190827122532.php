<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190827122532 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Provider` ADD `Description` VARCHAR(2000) DEFAULT NULL COMMENT 'Описание провайдера (поддерживает html). Использутся на страницах рейтинга провайдера'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Provider` DROP `Description`");
    }
}
