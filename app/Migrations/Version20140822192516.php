<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140822192516 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr add GoogleAuthSecret varchar(16) comment 'секрет для двухфакторной аутентификации'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Usr drop GoogleAuthSecret");
    }
}
