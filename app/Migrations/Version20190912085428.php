<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190912085428 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("delete from AwardIntervalDateRegion");
        $this->addSql("delete from DealRegion");
        $this->addSql("delete from Region where Kind is null or Kind <> " . REGION_KIND_CONTINENT);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
