<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220628095352 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Trip ADD INDEX TripNumberIndex (RecordLocator)");
        $this->addSql("ALTER TABLE Reservation ADD INDEX ReservationNumberIndex (ConfirmationNumber)");
        $this->addSql("ALTER TABLE Rental ADD INDEX RentalNumberIndex (Number)");
        $this->addSql("ALTER TABLE Restaurant ADD INDEX RestaurantNumberIndex (ConfNo)");
        $this->addSql("ALTER TABLE Parking ADD INDEX ParkingNumberIndex (Number)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Trip DROP INDEX TripNumberIndex");
        $this->addSql("ALTER TABLE Reservation DROP INDEX ReservationNumberIndex");
        $this->addSql("ALTER TABLE Rental DROP INDEX RentalNumberIndex");
        $this->addSql("ALTER TABLE Restaurant DROP INDEX RestaurantNumberIndex");
        $this->addSql("ALTER TABLE Parking DROP INDEX ParkingNumberIndex");
    }
}
