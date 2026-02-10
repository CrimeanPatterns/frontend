<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171130100018 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("drop table Trade");
        $this->addSql("drop table TradeFAQ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
