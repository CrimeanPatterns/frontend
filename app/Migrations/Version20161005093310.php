<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Globals\StringHandler;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161005093310 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $stmt = $this->connection->executeQuery("SELECT u.UserID as id FROM Usr u WHERE u.refcode IS NULL");

        while ($user = $stmt->fetch()) {
            $this->connection->executeUpdate("UPDATE Usr u SET u.refcode = ? WHERE u.UserID = ?", [StringHandler::getPseudoRandomString(10), $user['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
