<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230219051714 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Lounge
                DROP OpeningHours,
                ADD OpeningHours JSON DEFAULT NULL COMMENT 'Время работы' AFTER Gate2;
        ");
        $this->addSql("
            ALTER TABLE LoungeSource
                ADD OpeningHoursNew JSON DEFAULT NULL COMMENT 'Время работы' AFTER OpeningHoursData;
        ");
        $this->addSql("
            UPDATE
                LoungeSource
            SET OpeningHoursNew = CASE
                WHEN OpeningHoursData->'$.hours' IS NOT NULL AND OpeningHoursData->'$.tz' IS NOT NULL THEN OpeningHoursData
                WHEN SourceCode <> 'loungebuddy' AND OpeningHours IS NOT NULL AND OpeningHours <> '' THEN JSON_OBJECT('raw', OpeningHours)
            END;
        ");
        $this->addSql("
            ALTER TABLE LoungeSource
                DROP OpeningHours,
                DROP OpeningHoursData,
                CHANGE OpeningHoursNew OpeningHours JSON DEFAULT NULL COMMENT 'Время работы';
        ");
        $this->addSql("
            UPDATE Lounge AS l
            JOIN LoungeSource AS ls ON ls.LoungeID = l.LoungeID AND ls.SourceCode = 'loungebuddy'
            SET l.OpeningHours = ls.OpeningHours,
                l.IsAvailable = ls.IsAvailable;
        ");
        $this->addSql("
            CREATE TABLE LoungeAction (
                LoungeActionID INT NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
                LoungeID INT NOT NULL COMMENT 'Ссылка на лаундж',
                ActionCode VARCHAR(50) NOT NULL COMMENT 'Код действия',
                ActionData JSON DEFAULT NULL COMMENT 'Данные действия',
                CreateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
                UpdateDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата последнего обновления',
                PRIMARY KEY (LoungeActionID),
                CONSTRAINT LoungeAction_LoungeID_fk FOREIGN KEY (LoungeID) REFERENCES Lounge (LoungeID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB COMMENT 'Действия над лаунджами';
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
