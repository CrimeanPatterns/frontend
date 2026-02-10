<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170711091611 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table `CardImage` add index `CardImage_UploadDate` (`UploadDate`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `CardImage` drop index `CardImage_UploadDate`');
    }
}
