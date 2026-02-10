<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130807171840 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->connection->insert("SiteGroup", ["GroupName" => "Agents", "Description" => "Booking agents"]);
        $this->write("User group 'Agents' added");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM SiteGroup WHERE GroupName = 'Agents'");
        $this->write("User group 'Agents' removed");
    }
}
