<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Sitegroup;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200113122443 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("insert into SiteGroup (GroupName, Description) values (?, 'Users with detected business cards')", [Sitegroup::GROUP_BUSINESS_DETECTED]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('delete from SiteGroup where GroupName = ?', [Sitegroup::GROUP_BUSINESS_DETECTED]);
    }
}
