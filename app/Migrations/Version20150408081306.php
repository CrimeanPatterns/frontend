<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150408081306 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
          UPDATE Usr u LEFT OUTER JOIN AbBookerInfo i on i.UserID = u.DefaultBookerID
          SET u.DefaultBookerID = NULL
          WHERE u.DefaultBookerID IS NOT NULL AND i.UserID IS NULL
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
