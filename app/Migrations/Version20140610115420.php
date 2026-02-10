<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140610115420 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SiteAd ADD ReferralPartnerID int unsigned null');
        $this->addSql('update SiteAd set ReferralPartnerID=116000 where siteadid = 141');
        $this->addSql("delete from SiteGroup where GroupName = ?", ['staff:booker:restrict']);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('Sitead')->dropColumn('ReferralPartnerID');
    }
}
