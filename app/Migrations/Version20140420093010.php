<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140420093010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr ADD EmailBooking TINYINT  UNSIGNED  NOT NULL  DEFAULT '1' COMMENT 'Отправлять ли букеру письма, связанные с букингом' AFTER EmailOffers;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Usr DROP EmailBooking;");
    }
}
