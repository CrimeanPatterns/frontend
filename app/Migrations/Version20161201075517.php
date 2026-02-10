<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161201075517 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->executeQuery("ALTER TABLE Usr ADD IosReceipt TEXT NULL COMMENT 'iOS рецепт для доступа к серверу apple';");
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("SELECT * FROM InAppIOSReceipt");

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->connection->update("Usr", ["IosReceipt" => $row["Receipt"]], ["UserID" => $row["UserID"]]);
            }
            $this->connection->executeQuery("DROP TABLE InAppIOSReceipt;");
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP IosReceipt");
    }
}
