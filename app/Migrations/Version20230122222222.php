<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230122222222 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `QsTransaction` CHANGE `CTR` `CTR` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT '0'");
    }

    public function down(Schema $schema): void
    {
    }
}
