<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170130175704 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE AbBookerInfo SET MerchantName = 'ABROADERS' WHERE UserID = '221732'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE AbBookerInfo SET MerchantName = NULL WHERE UserID = '221732'");
    }
}
