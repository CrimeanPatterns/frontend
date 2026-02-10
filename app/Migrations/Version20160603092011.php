<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160603092011 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `EmailTemplate` (
              `EmailTemplateID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Для EmailLog',
              `Code` varchar(250) NOT NULL COMMENT 'Уникальный код для поиска data provider',
              `Subject` varchar(250) DEFAULT NULL COMMENT 'Subject письма',
              `Body` text DEFAULT NULL COMMENT 'Тело письма',
              `Head` text DEFAULT NULL COMMENT 'Блок внутри head',
              `Style` text DEFAULT NULL COMMENT 'Блок style',
              `Layout` varchar(250) DEFAULT 'offer' COMMENT 'Основной шаблон письма',
              `CreateDate` datetime NOT NULL COMMENT 'Дата создания',
              `UpdateDate` datetime NOT NULL COMMENT 'Дата последнего обновления',
              `RenderEngine` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Метод рендера subject/тела письма',
              `Type` tinyint(1) NOT NULL  DEFAULT 3 COMMENT 'Тип письма: оффер - 1, product update - 2 или другое - 3',
              `Enabled` tinyint(1) NOT NULL  DEFAULT 0 COMMENT 'Вкл/Откл',
              PRIMARY KEY (`EmailTemplateID`),
              UNIQUE KEY `Code` (`Code`)
            ) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE `EmailTemplate`;
        ");
    }
}
