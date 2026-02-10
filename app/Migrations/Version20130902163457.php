<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130902163457 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('create index idxProviderID on Trip(ProviderID)');
        $this->addSql('create index idxProviderID on Reservation(ProviderID)');
        $this->addSql('create index idxProviderID on Rental(ProviderID)');
        $this->addSql('create index idxProviderID on Restaurant(ProviderID)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop index idxProviderID on Trip');
        $this->addSql('drop index idxProviderID on Reservation');
        $this->addSql('drop index idxProviderID on Rental');
        $this->addSql('drop index idxProviderID on Restaurant');
    }
}
