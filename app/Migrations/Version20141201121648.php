<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20141201121648 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Add "Region" table description
        $this->addSql("ALTER TABLE `Region` COMMENT  ?;", ['Географический регион (от континента до конкретного адреса)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Name` varchar(120) NOT NULL  COMMENT  ?;", ['Название'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Kind` int(11) DEFAULT NULL  COMMENT  ?;", ['Тип (константа из web/kernel/constants.php, например REGION_KIND_CONTINENT)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Address` varchar(120) DEFAULT NULL  COMMENT  ?;", ['Адрес'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Zip` varchar(10) DEFAULT NULL  COMMENT  ?;", ['Почтовый код'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `URL` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Интернет адрес'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `AddressText` varchar(2000) DEFAULT NULL  COMMENT  ?;", ['Подробный адрес и ещё некоторые детали (телефон, факс и т.д.)'], [\PDO::PARAM_STR]);

        // Add "RegionContent" table description
        $this->addSql("ALTER TABLE `RegionContent` COMMENT  ?;", ['Связи между регионами из таблицы Region'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RegionContent` MODIFY `RegionID` int(11) NOT NULL  COMMENT  ?;", ['ID из таблицы Region'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RegionContent` MODIFY `SubRegionID` int(11)  COMMENT  ?;", ['ID региона, являющегося дочерним для текущего'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RegionContent` MODIFY `Exclude` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Инверсия, если выставлено в 1, то SubRegionID исключён из родительского'], [\PDO::PARAM_STR]);

        // "RentalCompany" table description
        $this->addSql("ALTER TABLE `RentalCompany` COMMENT  ?;", ['Компании предоставляющие услуги по аренде машин'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RentalCompany` MODIFY `Name` varchar(200) NOT NULL  COMMENT  ?;", ['Название компании'], [\PDO::PARAM_STR]);
    }

    public function down(Schema $schema): void
    {
        // Remove "Region" table description
        $this->addSql("ALTER TABLE `Region` COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Name` varchar(120) NOT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Kind` int(11) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Address` varchar(120) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `Zip` varchar(10) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `URL` varchar(250) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Region` MODIFY `AddressText` varchar(2000) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);

        // Remove "RegionContent" table description
        $this->addSql("ALTER TABLE `RegionContent` COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RegionContent` MODIFY `RegionID` int(11) NOT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RegionContent` MODIFY `SubRegionID` int(11)  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RegionContent` MODIFY `Exclude` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", [''], [\PDO::PARAM_STR]);

        // Remove "RentalCompany" table description
        $this->addSql("ALTER TABLE `RentalCompany` COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `RentalCompany` MODIFY `Name` varchar(200) NOT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
    }
}
