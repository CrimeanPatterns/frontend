<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230411113711 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $comment = 'От какого провайдера получили SpentAwards';

        $this->addSql("
            ALTER TABLE Trip ADD SpentAwardsProviderID INT NULL COMMENT '$comment' AFTER SpentAwards,
                ADD CONSTRAINT fk_SpentAwardsTripProviderID FOREIGN KEY (SpentAwardsProviderID) REFERENCES Provider(ProviderID) ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE Reservation ADD SpentAwardsProviderID INT NULL COMMENT '$comment' AFTER SpentAwards,
                ADD CONSTRAINT fk_SpentAwardsReservationProviderID FOREIGN KEY (SpentAwardsProviderID) REFERENCES Provider(ProviderID) ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE Rental ADD SpentAwardsProviderID INT NULL COMMENT '$comment' AFTER SpentAwards,
                ADD CONSTRAINT fk_SpentAwardsRentalProviderID FOREIGN KEY (SpentAwardsProviderID) REFERENCES Provider(ProviderID) ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE Restaurant ADD SpentAwardsProviderID INT NULL COMMENT '$comment' AFTER SpentAwards,
                ADD CONSTRAINT fk_SpentAwardsRestaurantProviderID FOREIGN KEY (SpentAwardsProviderID) REFERENCES Provider(ProviderID) ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE Parking ADD SpentAwardsProviderID INT NULL COMMENT '$comment' AFTER SpentAwards,
                ADD CONSTRAINT fk_SpentAwardsParkingProviderID FOREIGN KEY (SpentAwardsProviderID) REFERENCES Provider(ProviderID) ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE Trip DROP FOREIGN KEY fk_SpentAwardsTripProviderID, DROP COLUMN SpentAwardsProviderID");
        $this->addSql("ALTER TABLE Reservation DROP FOREIGN KEY fk_SpentAwardsReservationProviderID, DROP COLUMN SpentAwardsProviderID");
        $this->addSql("ALTER TABLE Rental DROP FOREIGN KEY fk_SpentAwardsRentalProviderID, DROP COLUMN SpentAwardsProviderID");
        $this->addSql("ALTER TABLE Restaurant DROP FOREIGN KEY fk_SpentAwardsRestaurantProviderID, DROP COLUMN SpentAwardsProviderID");
        $this->addSql("ALTER TABLE Parking DROP FOREIGN KEY fk_SpentAwardsParkingProviderID, DROP COLUMN SpentAwardsProviderID");
    }
}
