<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171210053703 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->executeQuery("ALTER TABLE ImpersonateLog MODIFY IPAddress VARCHAR(60) NULL");
        $this->connection->executeQuery("ALTER TABLE MobileDevice MODIFY IP VARCHAR(60) NULL");
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("SELECT MobileDeviceID, IP FROM MobileDevice WHERE IP REGEXP '^[0-9a-fA-F]{1,4}:'");

            while ($row = $stmt->fetch()) {
                if (!empty($row['IP']) && !filter_var($row['IP'], FILTER_VALIDATE_IP)) {
                    $this->connection->executeQuery("UPDATE MobileDevice SET IP = NULL WHERE MobileDeviceID = ?", [
                        $row['MobileDeviceID'],
                    ], [\PDO::PARAM_INT]);
                }
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE ImpersonateLog MODIFY IPAddress VARCHAR(15) NULL;
            ALTER TABLE MobileDevice MODIFY IP VARCHAR(20) NULL;
        ");
    }
}
