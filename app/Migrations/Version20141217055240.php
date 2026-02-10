<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20141217055240 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update Account set PassChangeDate = CreationDate where PassChangeDate is null");
    }

    public function down(Schema $schema): void
    {
    }
}
