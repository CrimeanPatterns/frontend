<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Statement;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161006113030 extends AbstractMigration implements ContainerAwareInterface
{
    public const TABLE_MAP = [
        'S' => 'TripSegment',
        'E' => 'Restaurant',
        'R' => 'Reservation',
        'L' => 'Rental',
    ];
    /**
     * @var ContainerInterface
     */
    private $container;

    public function up(Schema $schema): void
    {
        $this->connection->executeQuery("ALTER TABLE Restaurant ADD ChangeDate DATETIME DEFAULT NULL COMMENT 'Одно из свойств этого сегмента было изменено на сайте провайдера'");
        $this->connection->executeQuery("ALTER TABLE Reservation ADD ChangeDate DATETIME DEFAULT NULL COMMENT 'Одно из свойств этого сегмента было изменено на сайте провайдера'");
        $this->connection->executeQuery("ALTER TABLE Rental ADD ChangeDate DATETIME DEFAULT NULL COMMENT 'Одно из свойств этого сегмента было изменено на сайте провайдера'");

        $this->connection->transactional(function () {
            $stmt = $this->container->get("doctrine.dbal.unbuffered_connection")->executeQuery("
                SELECT 
                    MAX(dc.ChangeDate) maxChangeDate,
                    SourceID
                FROM DiffChange dc
                GROUP BY dc.SourceID
            ");

            /** @var Statement[] $preparedByKind */
            $preparedByKind = [];

            foreach (self::TABLE_MAP as $kind => $table) {
                $preparedByKind[$kind] = $this->connection->prepare(sprintf("UPDATE `%s` SET `ChangeDate` = ? WHERE `%s` = ?", $table, $table . 'ID'));
            }

            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as [$maxChangeDate, $sourceId]) {
                $stmt = $preparedByKind[substr($sourceId, 0, 1)];
                $stmt->bindValue(1, $maxChangeDate, \PDO::PARAM_STR);
                $stmt->bindValue(2, substr($sourceId, 2), \PDO::PARAM_STR);
                $stmt->execute();
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Restaurant DROP ChangeDate");
        $this->addSql("ALTER TABLE Reservation DROP ChangeDate");
        $this->addSql("ALTER TABLE Rental DROP ChangeDate");
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
