<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180115124246 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Cart MODIFY PurchaseToken TEXT DEFAULT NULL COMMENT 'Необходим для андройда и ios, получение инфы о платеже';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Cart MODIFY PurchaseToken VARCHAR(1000) DEFAULT NULL COMMENT 'Необходим для андройда, получение инфы о платеже';");
    }
}
