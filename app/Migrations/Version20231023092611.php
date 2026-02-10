<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231023092611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if (!$schema->hasTable('RAFlightHardLimit')) {
            $this->addSql(/** @lang MySQL */ " 
                CREATE TABLE `RAFlightHardLimit` (
		    	  `RAFlightHardLimitID` int(11) NOT NULL AUTO_INCREMENT,
    			  `ProviderID` int(11) NOT NULL,
	    		  `ClassOfService` varchar(40) NOT NULL,
		    	  `Base` int(10) NOT NULL,
    			  `Multiplier` int(10) NOT NULL,
	    		  `HardCap` int(10) NOT NULL,
		    	  PRIMARY KEY (`RAFlightHardLimitID`),
                  CONSTRAINT `RAFlightHardLimit_ProviderID_fk` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE,
                 UNIQUE KEY `idxRAFlightHardLimitFilterKey` (`ProviderID`,`ClassOfService`));
              ");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        if ($schema->hasTable('RAFlightHardLimit')) {
            $this->addSql(/** @lang MySQL */ " 
                DROP TABLE `RAFlightHardLimit`;");
        }
    }
}
