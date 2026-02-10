<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180126121855 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE 
                CartItem ci
                JOIN Cart c ON c.CartID = ci.CartID
            SET ci.ScheduledDate = NULL 
            WHERE
                c.PayDate IS NOT NULL
                AND c.PaymentType IN (8, 9);
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
