<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210505061629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Share Travel Plans in the booking request thread';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbRequest 
            ADD PlanID INT NULL COMMENT 'Ссылка на травел план' AFTER BusinessTravel,
            ADD CONSTRAINT `FK_AbRequestPlanID` FOREIGN KEY (`PlanID`) REFERENCES `Plan` (`PlanID`) ON DELETE SET NULL;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE AbRequest 
                DROP FOREIGN KEY `FK_AbRequestPlanID`,
                DROP PlanID;
        ");
    }
}
