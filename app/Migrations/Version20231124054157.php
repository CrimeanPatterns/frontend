<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\FrameworkExtension\Migrations\ContainerAwareMigrationInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231124054157 extends AbstractMigration implements ContainerAwareMigrationInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema): void
    {
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $conn = $em->getConnection();

        $conn->executeStatement("
            ALTER TABLE RAFlightSearchRoute
            ADD COLUMN DepCode VARCHAR(3) NULL COMMENT 'Код аэропорта отправления' AFTER RAFlightSearchResponseID,
            ADD COLUMN ArrCode VARCHAR(3) NULL COMMENT 'Код аэропорта прибытия' AFTER DepCode,
            ADD COLUMN TimesFound INT NOT NULL DEFAULT 1 COMMENT 'Сколько раз был найден этот перелет',
            ADD COLUMN LastSeenDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Когда последний раз был найден этот перелет',
            ADD COLUMN Archived TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Перелет архивирован',
            ADD INDEX RAFlightSearchRoute_DepCode (DepCode),
            ADD INDEX RAFlightSearchRoute_ArrCode (ArrCode),
            ADD INDEX RAFlightSearchRoute_TimesFound (TimesFound),
            ADD INDEX RAFlightSearchRoute_LastSeenDate (LastSeenDate),
            ADD INDEX RAFlightSearchRoute_Archived (Archived),
            ADD INDEX RAFlightSearchRoute_Taxes (Taxes),
            ADD INDEX RAFlightSearchRoute_TotalDistance (TotalDistance)
        ");
        $conn->executeStatement("
            UPDATE RAFlightSearchRoute r JOIN RAFlightSearchResponse res ON r.RAFlightSearchResponseID = res.RAFlightSearchResponseID
            SET r.LastSeenDate = res.RequestDate;
        ");

        for ($i = 1; $i <= 2; $i++) {
            $this->updateDepArrCodes();
            $count = $conn->fetchOne("
                SELECT COUNT(*)
                FROM RAFlightSearchRoute
                WHERE DepCode IS NULL OR ArrCode IS NULL
            ");

            if ($count === 0) {
                break;
            }

            if ($i === 2) {
                $conn->executeQuery("
                    DELETE FROM RAFlightSearchRoute
                    WHERE DepCode IS NULL OR ArrCode IS NULL
                ");
            }
        }

        $conn->executeStatement("
            ALTER TABLE RAFlightSearchRoute
            MODIFY COLUMN DepCode VARCHAR(3) NOT NULL COMMENT 'Код аэропорта отправления',
            MODIFY COLUMN ArrCode VARCHAR(3) NOT NULL COMMENT 'Код аэропорта прибытия';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RAFlightSearchRoute
            DROP INDEX RAFlightSearchRoute_DepCode,
            DROP INDEX RAFlightSearchRoute_ArrCode,
            DROP INDEX RAFlightSearchRoute_TimesFound,
            DROP INDEX RAFlightSearchRoute_LastSeenDate,
            DROP INDEX RAFlightSearchRoute_Archived,
            DROP INDEX RAFlightSearchRoute_Taxes,
            DROP INDEX RAFlightSearchRoute_TotalDistance,
            DROP COLUMN DepCode,
            DROP COLUMN ArrCode,
            DROP COLUMN TimesFound,
            DROP COLUMN LastSeenDate,
            DROP COLUMN Archived;
        ");
    }

    private function updateDepArrCodes(): void
    {
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $conn = $em->getConnection();

        $sql = "
            SELECT RAFlightSearchRouteID
            FROM RAFlightSearchRoute
            WHERE DepCode IS NULL OR ArrCode IS NULL
        ";

        while ($id = $conn->fetchOne($sql)) {
            $conn->executeStatement("
                UPDATE RAFlightSearchRoute r
                SET r.DepCode = (
                    SELECT rs.DepCode
                    FROM RAFlightSearchRouteSegment rs
                    WHERE rs.RAFlightSearchRouteID = r.RAFlightSearchRouteID
                    ORDER BY rs.RAFlightSearchRouteSegmentID ASC
                    LIMIT 1
                ), r.ArrCode = (
                    SELECT rs.ArrCode
                    FROM RAFlightSearchRouteSegment rs
                    WHERE rs.RAFlightSearchRouteID = r.RAFlightSearchRouteID
                    ORDER BY rs.RAFlightSearchRouteSegmentID DESC
                    LIMIT 1
                )
                WHERE r.RAFlightSearchRouteID = :id
            ", ['id' => $id]);
        }
    }
}
