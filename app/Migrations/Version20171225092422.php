<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171225092422 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE Location l
              JOIN Account a ON a.AccountID = l.AccountID
              JOIN Provider p ON p.ProviderID = a.ProviderID AND p.Code = 'wegmans'
            SET Radius = 75
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE Location l
              JOIN Account a ON a.AccountID = l.AccountID
              JOIN Provider p ON p.ProviderID = a.ProviderID AND p.Code = 'wegmans'
            SET Radius = 50
        ");
    }
}
