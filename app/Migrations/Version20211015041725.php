<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211015041725 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $stmt = $this->connection->executeQuery("
            SELECT AccountID, UpdateDate, LastChangeDate, SuccessCheckDate 
            FROM Account 
            WHERE UpdateDate > NOW() + INTERVAL 1 HOUR OR SuccessCheckDate > NOW() + INTERVAL 1 HOUR
        ");

        while ($row = $stmt->fetchAssociative()) {
            $newDate = new \DateTime();

            if (!empty($row['LastChangeDate'])) {
                $lastChangeDate = new \DateTime($row['LastChangeDate']);

                if ($lastChangeDate <= $newDate) {
                    $newDate = $lastChangeDate;
                } else {
                    $this->connection->executeStatement("UPDATE Account SET LastChangeDate = ? WHERE AccountID = ?", [
                        $newDate->format('Y-m-d H:i:s'),
                        $row['AccountID'],
                    ]);
                    $this->connection->executeStatement("UPDATE AccountBalance SET UpdateDate = ? WHERE AccountID = ? AND UpdateDate = ? AND SubAccountID IS NULL", [
                        $newDate->format('Y-m-d H:i:s'),
                        $row['AccountID'],
                        $lastChangeDate->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            $this->connection->executeStatement("UPDATE Account SET UpdateDate = :date, SuccessCheckDate = :date WHERE AccountID = :id", [
                'id' => $row['AccountID'],
                'date' => $newDate->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
