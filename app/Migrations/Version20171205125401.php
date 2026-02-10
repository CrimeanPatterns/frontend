<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171205125401 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->executeQuery("ALTER TABLE RememberMeToken MODIFY IP VARCHAR(60) NOT NULL DEFAULT ''");
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("SELECT Series, Token, IP FROM RememberMeToken WHERE IP REGEXP '^[0-9a-fA-F]{1,4}:'");

            while ($row = $stmt->fetch()) {
                if (!empty($row['IP']) && !filter_var($row['IP'], FILTER_VALIDATE_IP)) {
                    $this->connection->executeQuery("UPDATE RememberMeToken SET IP = '' WHERE Series = ? AND Token = ?", [
                        $row['Series'], $row['Token'],
                    ], [\PDO::PARAM_STR, \PDO::PARAM_STR]);
                }
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE RememberMeToken MODIFY IP VARCHAR(15) NOT NULL DEFAULT '';
        ");
    }
}
