<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171103111009 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `RememberMeToken`
	            ADD `IP` VARCHAR(15) NOT NULL DEFAULT \'\' AFTER `LastUsed`,
	            ADD `UserAgent` VARCHAR(250) NULL DEFAULT NULL AFTER `IP`;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `RememberMeToken`
                DROP `IP`,
                DROP `UserAgent`;
        ');
    }
}
