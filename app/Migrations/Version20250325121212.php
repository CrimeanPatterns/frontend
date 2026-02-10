<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250325121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCardOffer`
                ADD `PrimaryPostID` BIGINT NULL DEFAULT NULL,
                ADD `SupportingPostID` VARCHAR(255) NULL DEFAULT NULL COMMENT 'ID блог постов через запятую', 
                ADD `OfferQuality` TINYINT(1) NULL DEFAULT NULL COMMENT 'see CreditCardOffer::OFFER_QUALITY_LIST', 
                ADD `IsMonetized` TINYINT(1) DEFAULT '0' 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `CreditCardOffer`
                DROP `PrimaryPostID`,
                DROP `SupportingPostID`,
                DROP `OfferQuality`,
                DROP `IsMonetized` 
        ');
    }
}
