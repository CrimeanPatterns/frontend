<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190314083936 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MileValue add RouteType varchar(3) not null default 'UNK' comment 'RT - roundtrip, OW - oneway, MC - multicity, see CalcMileValueCommand::ROUTE_ constants'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
