<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170329041635 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Rental` 
            MODIFY `PickupHours` VARCHAR(4096),
            MODIFY `DropoffHours` VARCHAR(4096)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Rental` 
            MODIFY `PickupHours` VARCHAR(80),
            MODIFY `DropoffHours` VARCHAR(80)
        ");
    }
}
