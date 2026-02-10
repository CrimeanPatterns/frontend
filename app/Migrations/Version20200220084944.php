<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200220084944 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MileValue add MilesSource char(1) comment 'see MileValue\Constants::MILE_SOURCE_'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
