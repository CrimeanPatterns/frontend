<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170510201903 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `CardImage` add column `ComputerVisionResult` mediumtext default null comment 'данные распознавания'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `CardImage` drop column `ComputerVisionResult`');
    }
}
