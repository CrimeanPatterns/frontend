<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220525130650 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE FlightStats (
                DepCode VARCHAR(3) NOT NULL COMMENT 'Код места отправления',
                DepDate DATETIME NOT NULL COMMENT 'Дата отправления',
                ArrCode VARCHAR(3) NOT NULL COMMENT 'Код места назначения',
                ArrDate DATETIME NOT NULL COMMENT 'Дата прибытия',
                FlightNumber VARCHAR(20) NOT NULL,
                FlightNumber2 VARCHAR(20) NOT NULL,
                DepTerminal VARCHAR(50) NULL COMMENT 'Терминал отправления',
                ArrTerminal VARCHAR(50) NULL COMMENT 'Терминал назначения',
                BookedAirline VARCHAR(3) NOT NULL,
                OperatingAirline VARCHAR(3) NOT NULL,
                PrimaryMarketingAirline VARCHAR(3) NOT NULL,
                CreateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата добавления',
                PRIMARY KEY (DepCode, DepDate, ArrCode, ArrDate, FlightNumber),
                KEY CreateDateIndex (CreateDate)
            ) ENGINE=InnoDB COMMENT 'Перелеты, которые записываются со FlightStats алертов для дальнейшего аналииза и заполнения AirportTerminal, AirportTerminalAlias';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE FlightStats;
        ");
    }
}
