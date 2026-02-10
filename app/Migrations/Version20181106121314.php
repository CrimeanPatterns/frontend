<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181106121314 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('update UserAgent set FirstName=null, LastName=null, Email=null where UserAgentID = 256');
        $this->addSql('update UserAgent set FirstName=null, LastName=null, Email=null where UserAgentID = 632');
    }

    public function down(Schema $schema): void
    {
    }
}
