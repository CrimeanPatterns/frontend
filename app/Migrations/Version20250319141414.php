<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20250319141414 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `PurchaseStat`
                ADD `DetailedText` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Текст на ссылке описания',
                ADD `DetailedLink` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Ссылка на описание',
                ADD `OfferLink` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Ссылка монетизации'; 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `PurchaseStat`
                DROP `DetailedText`,
                DROP `DetailedLink`,
                DROP `OfferLink`; 
        ');
    }
}
