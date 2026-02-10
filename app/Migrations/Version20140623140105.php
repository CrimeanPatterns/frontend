<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140623140105 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('update SiteAd set ReferralPartnerID=20606 where siteadid = 141');
        $this->addSql('update UserAgent set AccessLevel=7 where AgentID = 20606 and ClientID = 116000');
    }

    public function down(Schema $schema): void
    {
    }
}
