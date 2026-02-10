<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180123050738 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr ADD IosRestoredReceipt TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Восстановлены ли покупки на ios. 0 - нет, показываем попап о восстановлении.' AFTER IosReceipt;
            UPDATE 
                Usr u
                JOIN (
                    SELECT
                        DISTINCT c.UserID
                    FROM
                        Cart c
                        JOIN CartItem ci ON c.CartID = ci.CartID
                        JOIN Usr u on u.UserID = c.UserID
                    WHERE
                        c.PayDate IS NOT NULL
                        AND c.PaymentType = 8
                        AND ci.TypeID IN (16)
                        AND ci.Price > 0
                        AND u.IosReceipt IS NOT NULL AND u.IosReceipt <> ''
                ) t ON t.UserID = u.UserID
            SET u.IosRestoredReceipt = 0;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Usr DROP COLUMN IosRestoredReceipt;
        ");
    }
}
