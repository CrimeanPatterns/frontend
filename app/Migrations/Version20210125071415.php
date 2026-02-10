<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125071415 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `AwardChartAirlineGroup` (
          `AwardChartAirlineGroupID` int NOT NULL AUTO_INCREMENT,
          `AwardChartID` int NOT NULL,
          `AirlineGroupID` int NOT NULL,
          MinV int comment 'MinV for MinValue, because Min, MinValue - reserved words',
          MaxV int comment 'MaxV for MaxValue, because Max, MaxValue - reserved words',
          PRIMARY KEY (`AwardChartAirlineGroupID`),
          FOREIGN KEY `fkAirlineGroup` (`AirlineGroupID`) REFERENCES `AirlineGroup` (`AirlineGroupID`) ON DELETE CASCADE,
          FOREIGN KEY `fkAwardChart` (`AwardChartID`) REFERENCES `AwardChart` (`AwardChartID`) ON DELETE CASCADE
        ) ENGINE=InnoDB COMMENT='Часть схемы RewardsPrice'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table AwardChartAirlineGroup");
    }
}
