<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422073438 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("alter table MileValue 
            modify CustomTravelersCount tinyint comment 'TravelersCount edited by user',
            modify CustomTotalMilesSpent tinyint comment 'TotalMilesSpent edited by user',
            modify CustomTotalTaxesSpent tinyint comment 'TotalTaxesSpent edited by user',
            modify CustomAlternativeCost tinyint comment 'AlternativeCost edited by user'
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
