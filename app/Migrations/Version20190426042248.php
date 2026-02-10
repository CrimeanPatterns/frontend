<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190426042248 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Trip add TravelAgencyPhones varchar(250) comment 'array, serialized with comma'");
        $this->addSql("alter table Reservation add TravelAgencyPhones varchar(250) comment 'array, serialized with comma'");
        $this->addSql("alter table Rental add TravelAgencyPhones varchar(250) comment 'array, serialized with comma'");
        $this->addSql("alter table Restaurant add TravelAgencyPhones varchar(250) comment 'array, serialized with comma'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
