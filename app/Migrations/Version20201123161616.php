<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201123161616 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `QsTransaction` ADD `EstEarnings` DECIMAL(10,2) NULL DEFAULT '0.00' AFTER `Earnings`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `QsTransaction` DROP `EstEarnings`");
    }
}
