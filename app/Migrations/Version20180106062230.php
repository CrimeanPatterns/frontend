<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180106062230 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if (!$schema->getTable('Account')->hasColumn('DisableClientPasswordAccess')) {
            $this->addSql("ALTER TABLE `Account` ADD `DisableClientPasswordAccess` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Отключен доступ к паролю со стороны клиента, автологин, extension-обновление' AFTER `DisableAutologin`");
        }

//        $this->addSql('UPDATE Account SET DisableClientPasswordAccess = DisableAutologin WHERE DisableAutologin = 1');
//        $this->addSql('ALTER TABLE `Account` DROP `DisableAutologin`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Account` ADD `DisableAutologin` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Автологин отключён' AFTER `DisableExtension`");
    }
}
