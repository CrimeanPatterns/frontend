<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200228121350 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MileValue 
            add CustomTravelersCount tinyint not null default 0 comment 'TravelersCount edited by user',
            add CustomTotalMilesSpent tinyint not null default 0 comment 'TotalMilesSpent edited by user',
            add CustomTotalTaxesSpent tinyint not null default 0 comment 'TotalTaxesSpent edited by user',
            add CustomAlternativeCost tinyint not null default 0 comment 'AlternativeCost edited by user'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
