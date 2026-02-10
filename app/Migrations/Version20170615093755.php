<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170615093755 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table `EmailTemplate` add column `DataProvider` varchar(200) not null after `Code`');
        $this->addSql('UPDATE `EmailTemplate` SET `DataProvider` = `Code`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `EmailTemplate` drop column `DataProvider`');
    }
}
