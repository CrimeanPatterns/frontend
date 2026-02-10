<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181015085540 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            update 
                AirCode 
            set 
                TimeZone = TimeZone / 1000000 * 3600
            where 
                TimeZone is not null and 
                TimeZone <> 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            update 
                AirCode 
            set 
                TimeZone = TimeZone / 3600 * 1000000
            where 
                TimeZone is not null and 
                TimeZone <> 0
        ');
    }
}
