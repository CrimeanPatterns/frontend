<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231002080032 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge 
                CHANGE Valid Visible TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Показывать лаундж юзерам',
                CHANGE isAvailable IsAvailable TINYINT(1) NULL COMMENT 'Работает или нет. 0 - Нет / 1 - Да'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge 
                CHANGE Visible Valid TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Запись проверена и валидна',
                CHANGE IsAvailable isAvailable TINYINT(1) NULL COMMENT 'Работает или нет. 0 - Нет / 1 - Да'
        ");
    }
}
