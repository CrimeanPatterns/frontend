<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230627114837 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                ADD COLUMN DragonPassAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по DragonPass. 0 - Нет / 1 - Да' AFTER AmexPlatinumAccess,
                ADD COLUMN LoungeKeyAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по LoungeKey. 0 - Нет / 1 - Да' AFTER DragonPassAccess;
        ");
        $this->addSql("
            ALTER TABLE LoungeSource
                ADD COLUMN DragonPassAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по DragonPass. 0 - Нет / 1 - Да' AFTER AmexPlatinumAccess,
                ADD COLUMN LoungeKeyAccess TINYINT DEFAULT NULL COMMENT 'Есть ли доступ по LoungeKey. 0 - Нет / 1 - Да' AFTER DragonPassAccess;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                DROP COLUMN DragonPassAccess,
                DROP COLUMN LoungeKeyAccess;
        ");
        $this->addSql("
            ALTER TABLE LoungeSource
                DROP COLUMN DragonPassAccess,
                DROP COLUMN LoungeKeyAccess;
        ");
    }
}
