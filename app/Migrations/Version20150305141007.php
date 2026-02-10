<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150305141007 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Provider ADD RbxTargetHost varchar(80) DEFAULT NULL COMMENT "Сайт RetailBenefits, после всех редиректов"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Provider DROP RbxTargetHost');
    }
}
