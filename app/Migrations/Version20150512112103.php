<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150512112103 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table AbRequest add CONSTRAINT `FK_AbRequestStatusID` FOREIGN KEY (`InternalStatus`) REFERENCES `AbRequestStatus` (`AbRequestStatusID`) ON DELETE SET NULL;');
    }

    public function down(Schema $schema): void
    {
    }
}
