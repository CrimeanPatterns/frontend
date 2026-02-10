<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190618112038 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE CreditCard ADD COLUMN PointName VARCHAR(100) NULL DEFAULT NULL AFTER PointValue");
        $this->addSql('UPDATE CreditCard
        SET PointName = (
               CASE
                WHEN ProviderID = 84 THEN "Membership Rewards®"
                WHEN ProviderID = 87 THEN "Ultimate Rewards®"
                WHEN ProviderID = 364 THEN "ThankYou® Rewards"
                ELSE NULL
            END
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE CreditCard DROP PointName");
    }
}
