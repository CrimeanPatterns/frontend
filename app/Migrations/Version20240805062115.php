<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240805062115 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE RAFlightSearchRequest (
                RAFlightSearchRequestID VARCHAR(100) NOT NULL COMMENT 'Идентификатор запроса, возвращаемый сервисом RA Flights',
                RAFlightSearchQueryID INT NOT NULL COMMENT 'Ccылка на запрос',
                RequestDate DATETIME NOT NULL COMMENT 'Дата и время запроса к сервису RA Flights',
                ResponseDate DATETIME NULL COMMENT 'Дата и время получения ответа от сервиса RA Flights',
                PRIMARY KEY(RAFlightSearchRequestID),
                INDEX RAFlightSearchRequest_RequestDate_IDX (RequestDate),
                INDEX RAFlightSearchRequest_ResponseDate_IDX (ResponseDate),
                CONSTRAINT RAFlightSearchRequest_RAFlightSearchQuery_FK FOREIGN KEY (RAFlightSearchQueryID) REFERENCES RAFlightSearchQuery (RAFlightSearchQueryID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB CHARSET=utf8;
        ");
        $this->addSql('
            CREATE TABLE RAFlightSearchResponse (
                RAFlightSearchRequestID VARCHAR(100) NOT NULL,
                RAFlightSearchRouteID INT NOT NULL,
                PRIMARY KEY(RAFlightSearchRequestID, RAFlightSearchRouteID),
                CONSTRAINT RAFlightSearchResponse_RAFlightSearchRequest_FK FOREIGN KEY (RAFlightSearchRequestID) REFERENCES RAFlightSearchRequest (RAFlightSearchRequestID) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT RAFlightSearchResponse_RAFlightSearchRoute_FK FOREIGN KEY (RAFlightSearchRouteID) REFERENCES RAFlightSearchRoute (RAFlightSearchRouteID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB CHARSET=utf8;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE RAFlightSearchResponse');
        $this->addSql('DROP TABLE RAFlightSearchRequest');
    }
}
