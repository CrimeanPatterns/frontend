<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161130072522 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Provider SET EnableDate = '2016-09-01' WHERE EnableDate is null AND State = 1");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
