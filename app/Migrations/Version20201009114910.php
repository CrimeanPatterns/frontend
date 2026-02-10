<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201009114910 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider
            drop RegionalBusinessMileValue,
            drop GlobalBusinessMileValue,
            drop RegionalEconomyMileValue,
            drop GlobalEconomyMileValue,
            drop HotelPointValue,
            drop MileValueStatus,
            drop foreign key MPValueCertifiedUserID,
            drop MPValueCertifiedUserID,
            drop MPValueCertifiedDate    
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
