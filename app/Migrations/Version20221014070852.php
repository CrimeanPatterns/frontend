<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221014070852 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $commentComment = 'Полезная инфа для путешественника, возвращается парсером';
        $cancellationPolicyComment = 'Правила отмены';
        $this->addSql("
            ALTER TABLE Parking 
                ADD COLUMN Comment TEXT DEFAULT NULL COMMENT '$commentComment',
                ADD COLUMN CancellationPolicy MEDIUMTEXT DEFAULT NULL COMMENT '$cancellationPolicyComment',
                ADD COLUMN RateType VARCHAR(250) DEFAULT NULL COMMENT 'Тип тарифа',
                ALGORITHM = INSTANT;
            ALTER TABLE Rental 
                ADD COLUMN Comment TEXT DEFAULT NULL COMMENT '$commentComment',
                ADD COLUMN CancellationPolicy MEDIUMTEXT DEFAULT NULL COMMENT '$cancellationPolicyComment',
                ADD COLUMN DiscountDetails JSON DEFAULT NULL COMMENT 'Детали скидки',
                ADD COLUMN PricedEquipment JSON DEFAULT NULL COMMENT 'Дополнительное оплаченное оборудование',
                ALGORITHM = INSTANT;
            ALTER TABLE Reservation 
                ADD COLUMN Comment TEXT DEFAULT NULL COMMENT '$commentComment',
                ADD COLUMN ChainName VARCHAR(150) DEFAULT NULL COMMENT 'Название отельной сети',
                ADD COLUMN CancellationNumber VARCHAR(150) DEFAULT NULL COMMENT 'Номер отмены, если бронирование было отменено',
                ADD COLUMN NonRefundable TINYINT(1) DEFAULT NULL COMMENT '1 - бронь невозвратная, null - неизвестно возвратная или нет',
                ALGORITHM = INSTANT;
            ALTER TABLE Restaurant 
                ADD COLUMN Comment TEXT DEFAULT NULL COMMENT '$commentComment',
                ADD COLUMN CancellationPolicy MEDIUMTEXT DEFAULT NULL COMMENT '$cancellationPolicyComment',
                ADD COLUMN Seats JSON DEFAULT NULL COMMENT 'Зарезервированные места',
                ALGORITHM = INSTANT;
            ALTER TABLE Trip 
                ADD COLUMN Comment TEXT DEFAULT NULL COMMENT '$commentComment',
                ADD COLUMN CancellationPolicy MEDIUMTEXT DEFAULT NULL COMMENT '$cancellationPolicyComment',
                ALGORITHM = INSTANT;
            ALTER TABLE TripSegment
                ADD COLUMN Accommodations JSON DEFAULT NULL COMMENT 'Назначенные места для пассажиров на пароме',
                ADD COLUMN Vessel TINYTEXT DEFAULT NULL COMMENT 'Название или тип судна',
                ADD COLUMN Pets TINYTEXT DEFAULT NULL COMMENT 'Информация о домашних животных, указанных в билете',
                ADD COLUMN Vehicles JSON DEFAULT NULL COMMENT 'Транспортные средства, указанные в бронировании',
                ADD COLUMN Trailers JSON DEFAULT NULL COMMENT 'Прицепы, указанные в бронировании',
                ALGORITHM = INSTANT;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Parking 
                DROP COLUMN Comment,
                DROP COLUMN CancellationPolicy,
                DROP COLUMN RateType;
            ALTER TABLE Rental 
                DROP COLUMN Comment,
                DROP COLUMN CancellationPolicy,
                DROP COLUMN DiscountDetails,
                DROP COLUMN PricedEquipment;
            ALTER TABLE Reservation 
                DROP COLUMN Comment,
                DROP COLUMN ChainName,
                DROP COLUMN CancellationNumber,
                DROP COLUMN NonRefundable;
            ALTER TABLE Restaurant 
                DROP COLUMN Comment,
                DROP COLUMN CancellationPolicy,
                DROP COLUMN Seats;
            ALTER TABLE Trip 
                DROP COLUMN Comment,
                DROP COLUMN CancellationPolicy;
            ALTER TABLE TripSegment
                DROP COLUMN Accommodations,
                DROP COLUMN Vessel,
                DROP COLUMN Pets,
                DROP COLUMN Vehicles,
                DROP COLUMN Trailers;
        ');
    }
}
