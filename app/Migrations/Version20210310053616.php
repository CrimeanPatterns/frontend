<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210310053616 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update HotelPointValue set UpdateDate = CreateDate where UpdateDate is null");

        $this->addSql("
            ALTER TABLE HotelPointValue 
                ADD BrandID INT DEFAULT NULL COMMENT 'Бренд' AFTER HotelName,
                ADD CONSTRAINT HotelPointValueBrand FOREIGN KEY (BrandID) REFERENCES HotelBrand(HotelBrandID) ON DELETE SET NULL ON UPDATE CASCADE;
                
            ALTER TABLE HotelBrand 
                ADD MatchingPriority INT NOT NULL DEFAULT 0 COMMENT 'В каком порядке внутри провайдера будут происходить матчинги. От highest priority к lowest.' AFTER Patterns;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE HotelPointValue 
                DROP FOREIGN KEY HotelPointValueBrand,
                DROP BrandID;
                
            ALTER TABLE HotelBrand DROP MatchingPriority;
        ");
    }
}
