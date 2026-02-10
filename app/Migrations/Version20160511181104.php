<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160511181104 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update UserAgent ua JOIN Usr u on u.UserID = ua.AgentID set ua.TripAccessLevel = 1 where (Source = '*' or Source = 'T') and u.AccountLevel != 3 and ua.ClientID is not null");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
