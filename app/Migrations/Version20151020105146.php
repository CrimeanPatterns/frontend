<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20151020105146 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD RbxUserID INT DEFAULT NULL COMMENT 'ID пользователя в RetailBenefits' AFTER OwnedByManagerID");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP RbxUserID");
    }
}
