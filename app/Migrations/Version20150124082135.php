<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150124082135 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE AbRequest SET CameFrom = NULL WHERE CameFrom NOT IN(SELECT SiteAdID FROM SiteAd)");
        $this->addSql("ALTER TABLE AbRequest MODIFY CameFrom INT COMMENT 'Откуда мы получили этот запрос'");
        $this->addSql("ALTER TABLE AbRequest ADD FOREIGN KEY FK_CAMEFROM (CameFrom) REFERENCES SiteAd(SiteAdID) ON DELETE SET NULL ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE AbRequest DROP FOREIGN KEY AbRequest_ibfk_1");
    }
}
