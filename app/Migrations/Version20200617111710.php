<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200617111710 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table GeoTag add Source varchar(3) not null default 'g' comment 'Где мы нашли этот адрес, смотри GeoCodeSourceInterface::getSourceId'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
