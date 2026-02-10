<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170625075111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MobileDevice add Alias varchar(100) comment 'Алиас устройства'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table MobileDevice DROP Alias");
    }
}
