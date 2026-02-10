<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230618103004 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                MODIFY COLUMN isAvailable TINYINT DEFAULT NULL COMMENT 'Работает или нет. 0 - Нет / 1 - Да',
                MODIFY COLUMN PriorityPassAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                MODIFY COLUMN AmexPlatinumAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да';
        ");

        $this->addSql("
            ALTER TABLE LoungeSource
                MODIFY COLUMN IsAvailable TINYINT DEFAULT NULL COMMENT 'Работает или нет. 0 - Нет / 1 - Да',
                MODIFY COLUMN PriorityPassAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                MODIFY COLUMN AmexPlatinumAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                MODIFY COLUMN isAvailable TINYINT DEFAULT 1 NOT NULL COMMENT 'Работает или нет. 0 - Нет / 1 - Да',
                MODIFY COLUMN PriorityPassAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                MODIFY COLUMN AmexPlatinumAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да';
        ");

        $this->addSql("
            ALTER TABLE LoungeSource
                MODIFY COLUMN IsAvailable TINYINT DEFAULT 1 NOT NULL COMMENT 'Работает или нет. 0 - Нет / 1 - Да',
                MODIFY COLUMN PriorityPassAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по PriorityPass. 0 - Нет / 1 - Да',
                MODIFY COLUMN AmexPlatinumAccess TINYINT DEFAULT 0 NOT NULL COMMENT 'Есть ли доступ по AmexPlatinum. 0 - Нет / 1 - Да';
        ");
    }
}
