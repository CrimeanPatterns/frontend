<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171204114206 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("SELECT UserIPID, IP FROM UserIP WHERE IP REGEXP '^[0-9a-fA-F]{1,4}:'");

            while ($row = $stmt->fetch()) {
                if (!empty($row['IP']) && !filter_var($row['IP'], FILTER_VALIDATE_IP)) {
                    $this->connection->executeQuery("DELETE FROM UserIP WHERE UserIPID = ?", [$row['UserIPID']], [\PDO::PARAM_INT]);
                }
            }
        });
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
