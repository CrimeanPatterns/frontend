<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230602084713 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $stmt = $this->connection->executeQuery("
            SELECT DISTINCT u.UserID
            FROM Usr u
            JOIN (
                SELECT c.UserID
                FROM Cart c
                JOIN CartItem ci ON c.CartID = ci.CartID
                JOIN Usr u ON u.UserID = c.UserID
                WHERE c.PayDate IS NOT NULL
                    AND (
                        (
                            c.PaymentType IN (8, 9)
                            AND (
                                (
                                    (
                                        ci.TypeID = 16
                                        AND PayDate > (NOW() - INTERVAL 1 YEAR)
                                        )
                                    OR (
                                        ci.TypeID = 17
                                        AND PayDate > (NOW() - INTERVAL 1 WEEK)
                                        )
                                    )
                                OR (
                                    u.AccountLevel = 2
                                    AND (
                                        (
                                            ci.TypeID = 16
                                            AND PayDate > (NOW() - INTERVAL 1 YEAR - INTERVAL 7 DAY)
                                            )
                                        OR (
                                            ci.TypeID = 17
                                            AND PayDate > (NOW() - INTERVAL 1 WEEK - INTERVAL 7 DAY)
                                            )
                                        )
                                    )
                                )
                            )
                        OR (
                            c.PaymentType NOT IN (8, 9)
                            AND c.PaymentType <> 12
                            AND ci.TypeID IN (16, 17, 14, 201, 202, 203)
                            AND u.Subscription IS NOT NULL
                            )
                        )
                ) t ON t.UserID = u.UserID
            WHERE u.AccountLevel = 1
                AND (
                    u.Subscription IS NOT NULL
                    OR SubscriptionType IS NOT NULL
                    )
            ORDER BY u.UserID DESC;
        ");
        $affected = 0;

        while ($user = $stmt->fetchAssociative()) {
            $this->connection->executeQuery('UPDATE Usr SET Subscription = NULL, SubscriptionType = NULL, PayPalRecurringProfileID = NULL WHERE UserID = :userId', [
                'userId' => $user['UserID'],
            ]);
            $affected++;
        }

        $this->write(sprintf('Affected %d users', $affected));
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
