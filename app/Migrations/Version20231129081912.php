<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231129081912 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchRoute
                ADD COLUMN RAFlightSearchQueryID INT(11) NULL COMMENT 'Ссылка на запрос' AFTER RAFlightSearchRouteID,
                ADD COLUMN CreateDate DATETIME NULL COMMENT 'Дата создания' AFTER TimesFound,
                ADD COLUMN Parser VARCHAR(250) NULL COMMENT 'Парсер, который вернул данный раут' AFTER TotalDistance,
                ADD COLUMN ApiRequestID VARCHAR(100) NULL COMMENT 'ID запроса к API' AFTER Parser;
        ");
        $this->addSql("
            UPDATE RAFlightSearchRoute r
            JOIN RAFlightSearchResponse rs ON rs.RAFlightSearchResponseID = r.RAFlightSearchResponseID
            SET r.RAFlightSearchQueryID = rs.RAFlightSearchQueryID,
                r.CreateDate = rs.RequestDate,
                r.Parser = rs.Parser,
                r.ApiRequestID = rs.ApiRequestID;
        ");
        $this->addSql("
            ALTER TABLE RAFlightSearchRoute
                MODIFY COLUMN RAFlightSearchQueryID INT(11) NOT NULL COMMENT 'Ссылка на запрос',
                MODIFY COLUMN CreateDate DATETIME NOT NULL COMMENT 'Дата создания',
                MODIFY COLUMN Parser VARCHAR(250) NOT NULL COMMENT 'Парсер, который вернул данный раут',
                MODIFY COLUMN ApiRequestID VARCHAR(100) NOT NULL COMMENT 'ID запроса к API',
                ADD CONSTRAINT RAFlightSearchRoute_RAFlightSearchQueryID_fk FOREIGN KEY (RAFlightSearchQueryID) REFERENCES RAFlightSearchQuery (RAFlightSearchQueryID) ON DELETE CASCADE ON UPDATE CASCADE,
                DROP FOREIGN KEY RAFlightSearchRoute_RAFlightSearchResponseID_fk,
                DROP INDEX RAFlightSearchRoute_RAFlightSearchResponseID_fk,
                DROP COLUMN RAFlightSearchResponseID;
        ");
        $this->addSql("
            DROP TABLE RAFlightSearchResponse;
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
