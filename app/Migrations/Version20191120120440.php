<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191120120440 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table TripSegment add Sources json comment 'Откуда собран этот сегмент, подробнее в сущности'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
