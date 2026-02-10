<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200526102945 extends AbstractMigration
{
    public static $updates = [
        [25, 10320], //China
        [279, 10448], //Taiwan
        [270, 10403], //Netherlands
        [174, 10400], //Myanmar
        [278, 10526], //South Korea
    ];

    public function up(Schema $schema): void
    {
        $sql = '';

        foreach (self::$updates as [$oldId, $newId]) {
            $sql .= sprintf("UPDATE ProviderPhone SET RegionID = %s WHERE RegionID = %s;\n", $newId, $oldId);
        }
        $this->addSql($sql);

        $this->addSql("
            ALTER TABLE `ProviderPhone` 
                ADD `CountryID` INT(11)  NULL  AFTER `RegionID`,
                ADD CONSTRAINT `ProviderPhone_ibfk_3` FOREIGN KEY (`CountryID`) REFERENCES `Country` (`CountryID`);
            ");

        $this->addSql("UPDATE ProviderPhone pp JOIN Region r ON pp.RegionID = r.RegionID SET pp.CountryID = r.CountryID");
    }

    public function down(Schema $schema): void
    {
        $sql = '';

        foreach (self::$updates as [$newId, $oldId]) {
            $sql .= sprintf("UPDATE ProviderPhone SET RegionID = %s WHERE RegionID = %s;\n", $newId, $oldId);
        }

        $this->addSql($sql);

        $this->addSql("
            ALTER TABLE `ProviderPhone` 
                DROP FOREIGN KEY `ProviderPhone_ibfk_3`,
                DROP `CountryID`;
        ");
    }
}
