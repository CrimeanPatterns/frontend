<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200924052107 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table AppleUserInfo add Email varchar(250) not null");
        $this->addSql("delete from AppleUserInfo");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table AppleUserInfo drop Email");
    }
}
