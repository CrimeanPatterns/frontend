<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200821080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `QsTransaction` WHERE ClickDate >= '2020-08-01'");
        $this->addSql("DELETE FROM `QsTransaction` WHERE ProcessDate >= '2020-08-01'");
    }

    public function down(Schema $schema): void
    {
    }
}
