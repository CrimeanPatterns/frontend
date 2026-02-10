<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170912134538 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add Promo500k tinyint not null default 0 comment 'Попадает под промо 500 000'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop Promo500k");
    }
}
