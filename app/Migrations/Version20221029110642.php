<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221029110642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlight` CHANGE `TypeFlight` `FlightType` tinyint NOT NULL DEFAULT '1' COMMENT 'тип перелета: 1 - все перелеты провайдера, 2 - все перелеты партнеров, 3 - микс авиакомпаний'");
        $this->addSql("ALTER TABLE `RAFlight` ADD `IsFastest` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'флаг самого быстрого перелета в результате запроса по RequestId'");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxIsFastest` (`IsFastest`)");
        $this->addSql("ALTER TABLE `RAFlight` ADD `CabinType` varchar (20) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxCabinType` (`CabinType`)");
        $this->addSql("ALTER TABLE `RAFlight` ADD `ClassOfService` varchar (20) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE `RAFlight` ADD INDEX `idxClassOfService` (`ClassOfService`)");

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `RAFlight` CHANGE `FlightType` `TypeFlight` tinyint NOT NULL DEFAULT '0' COMMENT 'тип перелета: 0 - самый короткий, 1 - все перелеты првайдера, 2 - все перелеты партнеров, 3 - микс авиакомпаний'");
        $this->addSql("ALTER TABLE `RAFlight` DROP INDEX `idxIsFastest`");
        $this->addSql("ALTER TABLE `RAFlight` DROP `IsFastest`");
        $this->addSql("ALTER TABLE `RAFlight` DROP INDEX `idxCabinType`");
        $this->addSql("ALTER TABLE `RAFlight` DROP `CabinType`");

    }
}
