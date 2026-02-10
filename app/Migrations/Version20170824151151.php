<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170824151151 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE Trip SET ShareCode = substr(md5(concat(Trip.TripID, \'983hvc01jhsd083\')), 1, 20) WHERE ShareCode IS NULL');
        $this->addSql('ALTER TABLE Trip MODIFY ShareCode VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Trip MODIFY ShareCode VARCHAR(20)');
    }
}
