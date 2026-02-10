<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200221070345 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MileValue add `AlternativeBookingURL` varchar(2048) comment 'Ссылка на покупку, для дебага'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table MileValue drop `AlternativeBookingURL`");
    }
}
