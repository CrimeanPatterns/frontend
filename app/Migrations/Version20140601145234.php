<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140601145234 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbRequest ADD ByBooker TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '0'  AFTER CancelReason;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbRequest DROP ByBooker;");
    }
}
