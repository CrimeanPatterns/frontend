<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210521121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Hotel` ADD `Website` VARCHAR(2000) NULL DEFAULT NULL COMMENT 'Полный URL адрес отеля, страницы или отдельного сайта'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Hotel` DROP `Website`');
    }
}
