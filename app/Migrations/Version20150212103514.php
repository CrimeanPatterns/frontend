<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150212103514 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
			CREATE TABLE RewardsTransfer (
				RewardsTransferID INT NOT NULL AUTO_INCREMENT,
				SourceProviderID INT NOT NULL,
				TargetProviderID INT NOT NULL,
				SourceRate INT NOT NULL,
				TargetRate INT NOT NULL,
				Enabled TINYINT NOT NULL,
				PRIMARY KEY (RewardsTransferID),
				UNIQUE KEY (SourceProviderID, TargetProviderID),
				FOREIGN KEY (SourceProviderID) REFERENCES Provider(ProviderID),
				FOREIGN KEY (TargetProviderID) REFERENCES Provider(ProviderID)
			) ENGINE=InnoDB;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE RewardsTransfer');
    }
}
