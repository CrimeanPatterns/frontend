<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161129125018 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `FlightInfoLog` ADD COLUMN `RequestHash` char(32)");
        $this->addSql("UPDATE `FlightInfoLog` SET `RequestHash` = md5(`Request`)");
        $this->addSql('ALTER TABLE `FlightInfoLog` ADD KEY `idxLogServiceRequest` (`State`, `Service`, `RequestHash`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `FlightInfoLog` DROP KEY `idxLogServiceRequest`');
        $this->addSql('ALTER TABLE `FlightInfoLog` DROP COLUMN `RequestHash`');
    }
}
