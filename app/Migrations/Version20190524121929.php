<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190524121929 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table awardwallet.AirClassDictionary 
            drop key akSource,
            add unique key akAirlineCodeSource(AirlineCode, Source)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
