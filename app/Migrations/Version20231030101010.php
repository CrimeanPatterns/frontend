<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231030101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `QsTransaction` WHERE ClickDate >= '2023-08-01' OR ProcessDate >= '2023-08-01' ");
    }

    public function down(Schema $schema): void
    {
    }
}
