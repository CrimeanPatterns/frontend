<?php

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\CartItem\Booking;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161003112702 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->transactional(function () {
            $stmt = $this->connection->executeQuery("
                SELECT *
                FROM Usr
                WHERE AccountLevel = ?
            ", [ACCOUNT_LEVEL_AWPLUS]);

            while ($user = $stmt->fetch()) {
                $lifetimeContribution = $this->connection->executeQuery(
                    "
                    SELECT SUM(ci.Price * ci.Cnt * (100-ci.Discount)/100) LifetimeContribution
                    FROM   Cart c
                           JOIN CartItem ci
                           ON     c.CartID         = ci.CartID
                    WHERE  c.UserID                = ?
                           AND c.PayDate IS NOT NULL
                           AND (ci.TypeID <> ? OR ci.TypeID IS NULL)
                           AND
                           (
                                  ci.Price * ci.Cnt * ((100-ci.Discount)/100)
                           )
                           > 0
                    ",
                    [$user['UserID'], Booking::TYPE],
                    [\PDO::PARAM_INT]
                )->fetch(\PDO::FETCH_ASSOC)['LifetimeContribution'];

                if ($lifetimeContribution > 0) {
                    $discountedUpgrateDate = new \DateTime($user['PlusExpirationDate']);
                    $discountedUpgrateDate->modify('+1 month');
                    $discountedUpgrateDate = $discountedUpgrateDate->format('Y-m-d H:i:s');
                    $this->connection->executeQuery(
                        "
                          UPDATE Usr SET DiscountedUpgradeBefore = '{$discountedUpgrateDate}' WHERE UserID = {$user['UserID']}
                        ");
                }
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("update Usr set DiscountedUpgradeBefore = null");
    }
}
