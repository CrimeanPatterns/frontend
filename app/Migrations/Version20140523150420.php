<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140523150420 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE SiteAd ADD BookerID int unsigned null');
        $this->addSql('update SiteAd set BookerID=116000 where siteadid in (125, 141)');
        $this->addSql("delete from SiteGroup where GroupName = ?", ['staff:booker:restrict']);
        $this->addSql("INSERT INTO SiteGroup (SiteGroupID, GroupName, Description) VALUES (0, 'staff:booker:restrict', 'used in manager interface');");
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('Sitead')->dropColumn('BookerID');
        $this->addSql("delete from SiteGroup where GroupName = ?", ['staff:booker:restrict']);
    }
}
