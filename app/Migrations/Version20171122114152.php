<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171122114152 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `EmailTemplate`
                add column `Logo` varchar(60000) default null comment 'логотип' after `Subject`
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            alter table `EmailTemplate` drop column `Logo`
        ');
    }
}
