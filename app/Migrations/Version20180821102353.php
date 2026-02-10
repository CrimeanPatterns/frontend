<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180821102353 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        //Roundabout way to avoid table lock
        $this->addSql('ALTER TABLE Trip ADD COLUMN ShipCodeNew VARCHAR(10) DEFAULT NULL AFTER ShipCode');
        $this->addSql('UPDATE Trip SET ShipCodeNew = ShipCode');
        $this->addSql('ALTER TABLE Trip DROP COLUMN ShipCode');
        $this->addSql('ALTER TABLE Trip CHANGE COLUMN ShipCodeNew ShipCode VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Trip ADD COLUMN ShipCodeOld VARCHAR(2) DEFAULT NULL AFTER ShipCode');
        $this->addSql('UPDATE Trip SET ShipCodeOld = ShipCode');
        $this->addSql('ALTER TABLE Trip DROP COLUMN ShipCode');
        $this->addSql('ALTER TABLE Trip CHANGE COLUMN ShipCodeOld ShipCode VARCHAR(2) DEFAULT NULL');
    }
}
