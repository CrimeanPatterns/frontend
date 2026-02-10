<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180306115725 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Rental SET RentalCompanyName = ProviderName");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE Rental SET ProviderName = RentalCompanyName');
    }
}
