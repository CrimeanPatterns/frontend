<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190530111403 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE PromotionCard ADD COLUMN VisibleOnLanding tinyint NOT NULL DEFAULT 0 AFTER VisibleInList");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE PromotionCard drop VisibleOnLanding");
    }
}
