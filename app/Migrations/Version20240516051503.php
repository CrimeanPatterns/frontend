<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240516051503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table `ExtensionStat` modify column `Status` tinyint(1) not null default 0 comment 'Статус записи. 0 - ошибка, 1 - успешно, 2 - общее количество'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table `ExtensionStat` add column `Status` tinyint(1) not null comment 'Статус записи. 0 - ошибка, 1 - успешно, 2 - общее количество'");
    }
}
