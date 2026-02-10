<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141006133956 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // "HotelData" table description
        $this->addSql("ALTER TABLE `HotelData` COMMENT  ?;", ['Информация об отелях, в основном содержит данные о поинтах для соответствия той или иной категории отеля'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `HotelName` varchar(255) DEFAULT NULL  COMMENT  ?;", ['Название отеля'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `ProviderCode` varchar(64) DEFAULT NULL  COMMENT  ?;", ['Код провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `Category` int(11) DEFAULT NULL  COMMENT  ?;", ['Категория отеля (отличаются ценами,эксклюзивностью итд)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `MinPoints` int(11) DEFAULT NULL  COMMENT  ?;", ['Минимум поинтов для соответствия категории'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `MaxPoints` int(11) DEFAULT NULL  COMMENT  ?;", ['Максимум поинтов для соответствия категории'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `URL` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Url отеля'], [\PDO::PARAM_STR]);

        // "Hotel" table description
        $this->addSql("ALTER TABLE `Hotel` COMMENT  ?;", ['Информация об отелях, в основном содержит фото и общие данные об отеле, такие как адрес и кол-во звёзд'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Name` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['Название отеля'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Stars` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Кол-во звёзд'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `SmallPhoto` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['Уменьшенное фото'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `BigPhoto` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['Большое фото'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `MinRate` double NOT NULL DEFAULT '0'  COMMENT  ?;", ['Минимальная стоимость номера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `MaxRate` double NOT NULL DEFAULT '0'  COMMENT  ?;", ['Максимальная стоимость номера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `City` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['Город'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `State` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['Штат'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Zip` varchar(20) NOT NULL DEFAULT ''  COMMENT  ?;", ['Почтовый индекс'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Country` varchar(10) NOT NULL DEFAULT ''  COMMENT  ?;", ['Страна'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Address` varchar(250) NOT NULL DEFAULT ''  COMMENT  ?;", ['Адрес'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `PropertyID` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['ID из таблицы ProviderProperty, свойства таблицы'], [\PDO::PARAM_STR]);
    }

    public function down(Schema $schema): void
    {
        // "HotelData" table description
        $this->addSql("ALTER TABLE `HotelData` COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `HotelName` varchar(255) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `ProviderCode` varchar(64) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `Category` int(11) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `MinPoints` int(11) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `MaxPoints` int(11) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `HotelData` MODIFY `URL` varchar(250) DEFAULT NULL  COMMENT  ?;", [''], [\PDO::PARAM_STR]);

        // "Hotel" table description
        $this->addSql("ALTER TABLE `Hotel` COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Name` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Stars` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `SmallPhoto` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `BigPhoto` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `MinRate` double NOT NULL DEFAULT '0'  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `MaxRate` double NOT NULL DEFAULT '0'  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `City` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `State` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Zip` varchar(20) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Country` varchar(10) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `Address` varchar(250) NOT NULL DEFAULT ''  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Hotel` MODIFY `PropertyID` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [''], [\PDO::PARAM_STR]);
    }
}
