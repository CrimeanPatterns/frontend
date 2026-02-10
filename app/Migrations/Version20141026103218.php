<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141026103218 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // AAMembership table
        $this->addSql("ALTER TABLE `AAMembership` COMMENT  ?;", ['Члены American Airlines (#6873)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `FirstName` `FirstName` VARCHAR(30) NULL  DEFAULT NULL  COMMENT ?;", ['Имя'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `LastName` `LastName` VARCHAR(30)  NULL  DEFAULT NULL  COMMENT ?;", ['Фамилия'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Visits` `Visits` INT(10)  UNSIGNED  NULL  DEFAULT NULL  COMMENT ?;", ['Кол-во логинов на AW за сутки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Balance` `Balance` FLOAT  NULL  DEFAULT NULL  COMMENT ?;", ['Баланс аккаунта (aa или dividendmiles)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Expiration` `Expiration` DATETIME  NULL  DEFAULT NULL  COMMENT ?;", ['Дата протухания аккаунта (aa или dividendmiles)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Account` `Account` VARCHAR(80)  NULL  DEFAULT NULL  COMMENT ?;", ['Логин аккаунта (aa или dividendmiles)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Status` `Status` VARCHAR(40)  NULL  DEFAULT NULL  COMMENT ?;", ['Элитный статус аккаунта (aa или dividendmiles)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Tier1` `Tier1` INT(11)  NULL  DEFAULT NULL  COMMENT ?;", ['Кол-во аккаунтов Категории 1 (American Airlines, United Airlines, Delta Air Lines, Southwest Airlines). Категорию провайдера можно узнать из Provider.Category, #6873'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Tier2` `Tier2` INT(11)  NULL  DEFAULT NULL  COMMENT ?;", ['Кол-во аккаунтов Категории 2 (US Airways (Dividend Miles), British Airways (Executive Club), Alaska Airlines (Mileage Plan) и т.д.). Категорию провайдера можно узнать из Provider.Category, #6873'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `Tier3` `Tier3` INT(11)  NULL  DEFAULT NULL  COMMENT ?;", ['Кол-во аккаунтов Категории 3 (Не входящие в предыдущие две категории). Категорию провайдера можно узнать из Provider.Category, #6873'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `SnapDate` `SnapDate` DATETIME  NULL  DEFAULT NULL  COMMENT ?;", ['Дата запуска скрипта aw:update-aa-membership'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `UserID` `UserID` INT(10)  UNSIGNED  NULL  DEFAULT NULL  COMMENT ?;", ['Владелец аккаунта'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `AccountID` `AccountID` INT(10)  UNSIGNED  NULL  DEFAULT NULL  COMMENT ?;", ['Ссылка на аккаунт'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AAMembership` CHANGE `ProviderID` `ProviderID` INT(11)  NULL  DEFAULT NULL  COMMENT ?;", ['Ссылка на провайдера'], [\PDO::PARAM_STR]);

        $this->addSql("ALTER TABLE `VTLog` COMMENT  ?;", ['Import Virtually There itinerary log'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `VTLog` CHANGE `LogDate` `LogDate` DATETIME  NOT NULL  COMMENT ?;", ['Дата записи'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `VTLog` CHANGE `URL` `URL` VARCHAR(500)  NOT NULL  DEFAULT ''  COMMENT ?;", ['Ссылка на virtually there itinerary, типа https://www.virtuallythere.com/new/reservations'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `VTLog` CHANGE `UserID` `UserID` INT(11)  NOT NULL  COMMENT ?;", ['Импортирующий юзер'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `VTLog` CHANGE `Success` `Success` INT(11)  NOT NULL  COMMENT ?;", ['Успешность импорта. "-1" - ошибка'], [\PDO::PARAM_STR]);
    }

    public function down(Schema $schema): void
    {
    }
}
