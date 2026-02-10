<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201117114558 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MileValue add PriceAdjustment int comment 'На сколько была модифицирована цена полученная из источника цен. смотри CombinedPriceSource'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
