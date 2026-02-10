<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201008122127 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("insert into ProviderMileValue(
            ProviderID,
            RegionalEconomyMileValue,
            GlobalEconomyMileValue,
            RegionalBusinessMileValue,
            GlobalBusinessMileValue,
            HotelPointValue,
            Status,
            CertifiedByUserID,
            CertificationDate
        )
        select
            ProviderID,
            RegionalEconomyMileValue,
            GlobalEconomyMileValue,
            RegionalBusinessMileValue,
            GlobalBusinessMileValue,
            HotelPointValue,
            MileValueStatus as Status,
            MPValueCertifiedUserID as CertifiedByUserID,
            MPValueCertifiedDate as CertificationDate 
        from
            Provider
        where
            RegionalEconomyMileValue is not null
            or GlobalEconomyMileValue is not null
            or RegionalBusinessMileValue is not null
            or GlobalBusinessMileValue is not null
            or HotelPointValue is not null
            or MileValueStatus <> 0
            or MPValueCertifiedUserID is not null
            or MPValueCertifiedDate is not null
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
