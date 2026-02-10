<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171228111010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Track` CHANGE `CtID` `CtID` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Идентификатор пользователя eMiles'");
    }

    public function down(Schema $schema): void
    {
    }
}
