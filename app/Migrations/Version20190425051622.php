<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190425051622 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `SkyScannerDeals` (
              `DestinationAirport` varchar(3) NOT NULL,
              `OutboundDepartureDt` datetime NOT NULL,
              `SourceAirport` varchar(3) NOT NULL,
              `InboundDepartureDt` datetime NOT NULL,
              `Price` decimal(11,2) NOT NULL,
              `Median` decimal(11,2) NOT NULL,
              `MedianDeviation` decimal(11,2) DEFAULT NULL,
              `Currency` varchar(3) NOT NULL,
              `TargetURL` varchar(1000) NOT NULL,
              `Version` varchar(255) NOT NULL,
              PRIMARY KEY (`DestinationAirport`,`OutboundDepartureDt`,`SourceAirport`,`InboundDepartureDt`, `Version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("INSERT INTO Param (Name, Val) VALUE (?, ?)", [ParameterRepository::SKYSCANNER_DEALS_VERSION, 0]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `SkyScannerDeals`");
        $this->addSql("DELETE FROM Param WHERE Name = ?", [ParameterRepository::SKYSCANNER_DEALS_VERSION]);
    }
}
