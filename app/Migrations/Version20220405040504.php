<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220405040504 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `CustomEliteLevel` VARCHAR(128) NULL DEFAULT NULL COMMENT 'Кастомный уровень прописываемый пользователем', ALGORITHM = INSTANT");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Account` DROP `CustomEliteLevel`');
    }
}
