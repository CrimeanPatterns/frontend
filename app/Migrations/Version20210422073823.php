<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422073823 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("update MileValue set CustomTravelersCount = null where CustomTravelersCount = 0");
        $this->addSql("update MileValue set CustomTotalMilesSpent = null where CustomTotalMilesSpent = 0");
        $this->addSql("update MileValue set CustomTotalTaxesSpent = null where CustomTotalTaxesSpent = 0");
        $this->addSql("update MileValue set CustomAlternativeCost = null where CustomAlternativeCost = 0");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
