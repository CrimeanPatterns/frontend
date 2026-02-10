<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200310101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` ADD `ExcludeCardsId` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Список ID карт, для исключения показа при обнаружении карты у пользователя' AFTER `VisibleOnLanding`");
        $this->addSql("
            ALTER TABLE `CreditCard`
                CHANGE `CardFullName` `CardFullName` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Заголовок карты в виде банера на аккаунт листе',
                CHANGE `VisibleOnLanding` `VisibleOnLanding` TINYINT(1) NULL DEFAULT '0' COMMENT 'Yes/No - вывод на лэндинге',
                CHANGE `VisibleInList` `VisibleInList` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Yes/No - вывод на аккаунт листе',
                CHANGE `DirectClickURL` `DirectClickURL` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Прямая ссылка на оффер с карты',
                CHANGE `Text` `Text` TEXT NULL DEFAULT NULL COMMENT 'Описание под заголовком карты на аккаунт листе',
                CHANGE `SortIndex` `SortIndex` MEDIUMINT(8) NULL DEFAULT NULL COMMENT 'Порядок вывода'; 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `CreditCard` DROP `VisibleGroupId`');
    }
}
