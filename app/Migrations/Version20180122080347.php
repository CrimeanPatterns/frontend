<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180122080347 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Provider`
            ADD `CanChangePasswordClient` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Возможность смены паролей аккаунтов через extension',
            ADD `CanChangePasswordServer` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Возможность смены паролей аккаунтов через Loyalty';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Provider` 
            DROP COLUMN `CanChangePasswordClient`,
            DROP COLUMN `CanChangePasswordServer`;
        ");
    }
}
