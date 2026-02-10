<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230213060503 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE AwardSeason (
                AwardSeasonID INT NOT NULL AUTO_INCREMENT,
                Name VARCHAR(250) NOT NULL COMMENT 'Название сезона',
                UNIQUE KEY uniqName (Name),
                PRIMARY KEY (AwardSeasonID)
            ) ENGINE=InnoDB COMMENT 'Сезоны для наград';
        ");

        $this->addSql("
            INSERT INTO AwardSeason (Name)
            VALUES ('Off-Peak (Low Season)'),
                   ('Regular Season'),
                   ('Peak (High Season)');
        ");

        $this->addSql("
            CREATE TABLE AwardSeasonInterval (
                ProviderID INT NOT NULL COMMENT 'Провайдер',
                AwardSeasonID INT NOT NULL COMMENT 'Сезон',
                StartDate DATE NOT NULL COMMENT 'Начало сезона',
                EndDate DATE NOT NULL COMMENT 'Конец сезона',
                PRIMARY KEY (ProviderID, AwardSeasonID, StartDate),
                FOREIGN KEY fkProvider (ProviderID) REFERENCES Provider (ProviderID) ON DELETE CASCADE,
                FOREIGN KEY fkAwardSeason (AwardSeasonID) REFERENCES AwardSeason (AwardSeasonID) ON DELETE CASCADE
            ) ENGINE=InnoDB COMMENT 'Интервалы сезонов для наград';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DROP TABLE IF EXISTS AwardSeasonInterval;
            DROP TABLE IF EXISTS AwardSeason;
        ");
    }
}
