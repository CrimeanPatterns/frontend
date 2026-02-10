<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150126120948 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `MobileDevice` COMMENT  ?;", ['Устройства, на которые отсылаются push-уведомления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MobileDevice` MODIFY `DeviceKey` varchar(4096) NOT NULL  COMMENT  ?;", ['Идентификатор устройства, выданный Google\\Apple'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MobileDevice` MODIFY `DeviceType` tinyint(1) unsigned NOT NULL  COMMENT  ?;", ['Тип устройства, см. \\AwardWallet\\MainBundle\\Entity\\MobileDevice'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MobileDevice` MODIFY `UserID` int(11) NOT NULL  COMMENT  ?;", ['кому принадлежит устройство'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MobileDevice` MODIFY `CreationDate` datetime NOT NULL  COMMENT  ?;", ['Дата создания записи'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MobileDevice` ADD `Lang` VARCHAR(8) NOT NULL COMMENT 'Язык уведомления для устройства' AFTER `DeviceType`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `MobileDevice` DROP `Lang`');
    }
}
