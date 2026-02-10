<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200529080557 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table SiteAd add AppInstalls int unsigned null');
        $this->addSql('alter table SiteAd add LastAppInstall datetime  null');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table SiteAd drop column AppInstalls');
        $this->addSql('alter table SiteAd drop column LastAppInstall');
    }
}
