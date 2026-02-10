<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151112065940 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RetailBenefitsCashback COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE RetailBenefitsLink COLLATE = utf8_general_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RetailBenefitsCashback COLLATE = utf8_general_ci');
        $this->addSql('ALTER TABLE RetailBenefitsLink COLLATE = utf8_general_ci');
    }
}
