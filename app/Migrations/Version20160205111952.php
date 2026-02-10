<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160205111952 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE Account SET ExpirationDate = '2017-08-01 00:00:00', ExpirationAutoSet = 1 WHERE ProviderID = 36");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE Account set ExpirationDate = NULL, ExpirationAutoSet = 0 WHERE ProviderID = 36");
    }
}
