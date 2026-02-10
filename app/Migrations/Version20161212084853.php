<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161212084853 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $stmt = $this->connection->executeQuery("
            SELECT 
              AccountID
            FROM 
              AccountBalance 
            WHERE 
              UpdateDate >= NOW() - INTERVAL 2 YEAR
              AND Balance = 0 
              AND SubAccountID IS NULL 
            GROUP BY AccountID HAVING COUNT(*) > 1 ORDER BY AccountID
        ");

        while ($account = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stmt2 = $this->connection->executeQuery("
                SELECT 
                  *
                FROM 
                  AccountBalance 
                WHERE 
                  AccountID = ? AND SubAccountID IS NULL
                ORDER BY AccountBalanceID ASC
            ", [$account['AccountID']]);
            $last = null;

            while ($balance = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                if (!is_null($last) && $last == 0 && $balance['Balance'] == 0) {
                    $this->connection->delete("AccountBalance", ["AccountBalanceID" => $balance['AccountBalanceID']]);
                } else {
                    $last = $balance['Balance'];
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
