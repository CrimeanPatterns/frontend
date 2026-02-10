<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221125163909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlight` ADD `IsCheapest` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'флаг самого дешевого перелета в результате запроса по RequestId'");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxIsCheapest` (`IsCheapest`)");
        $this->addSql("ALTER TABLE `RAFlight` ADD `Passengers` TINYINT COMMENT 'Число пассажиров в поиске по RequestId'");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxPassengers` (`Passengers`)");
        $this->addSql("ALTER TABLE `RAFlight` ADD `SeatsLeft` varchar(10) COMMENT 'Через зпт число мест оставшиеся по сегментам'");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxSeatsLeft` (`SeatsLeft`)");
        $this->addSql("ALTER TABLE `RAFlight` ADD `SeatsLeftOnRoute` TINYINT  COMMENT 'Число оставшихся мест на весь маршрут'");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxSeatsLeftOnRoute` (`SeatsLeftOnRoute`)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlight` DROP `IsCheapest`");
        $this->addSql("ALTER TABLE `RAFlight` DROP `Passengers`");
        $this->addSql("ALTER TABLE `RAFlight` DROP `SeatsLeft`");
        $this->addSql("ALTER TABLE `RAFlight` DROP `SeatsLeftOnRoute`");

    }
}
