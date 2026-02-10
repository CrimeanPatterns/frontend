<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200819130000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `QsTransaction` WHERE ClickDate >= '2020-08-01' AND ClickDate < '2020-09-01'");
    }

    public function down(Schema $schema): void
    {
    }
}
