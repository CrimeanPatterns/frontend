<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221124091429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            CREATE TABLE `RAFlightStat` (
                `RAFlightStatID` int NOT NULL AUTO_INCREMENT,
                `Provider` varchar(20) NOT NULL COMMENT 'код провайдера',
                `Carrier` varchar(2) NOT NULL COMMENT 'iata код авиалинии',
                `FirstSeen` datetime NOT NULL COMMENT 'время когда впервые увидели перелет соответствующей авиалинии у провайдера',
                `LastSeen` datetime NOT NULL COMMENT 'время когда последний раз видели перелет соответствующей авиалинии у провайдера',
                PRIMARY KEY (`RAFlightStatID`),
                UNIQUE KEY `ProviderCarrierIndex` (`Provider`,`Carrier`)
                ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='статистика появления авиалиний в перелетах Reward Availability'            
            ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DROP TABLE RAFlightStat");

    }
}
