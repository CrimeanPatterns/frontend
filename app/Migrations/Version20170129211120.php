<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170129211120 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table BusinessSubscrChangeTypeLog");
        $this->addSql("drop table BusinessSubscrChangeUsersLog");
        $this->addSql("drop table BusinessSubscription");
        $this->addSql("drop table BusinessPrice");
    }
}
