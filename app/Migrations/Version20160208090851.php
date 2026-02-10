<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * add TripAccesLevel property to Useragent entity.
 */
class Version20160208090851 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserAgent add TripAccessLevel tinyint not null default 0 COMMENT 'Уровень доступа к таймлайну' AFTER SendEmails");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table UserAgent drop column TripAccessLevel');
    }
}
