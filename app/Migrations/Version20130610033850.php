<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20130610033850 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table TravelPlan add CustomUserAgent tinyint not null default 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table TravelPlan drop CustomUserAgent");
    }
}
