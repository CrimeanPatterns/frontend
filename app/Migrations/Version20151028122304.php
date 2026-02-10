<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151028122304 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('update Account set UseFrugalAutologin = null where UseFrugalAutologin = 0');
    }

    public function down(Schema $schema): void
    {
    }
}
