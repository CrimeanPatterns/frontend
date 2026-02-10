<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230516090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `QsCreditCard` ADD `IsHidden` TINYINT(1) NOT NULL DEFAULT '0' AFTER `IsManual`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `QsCreditCard` DROP `IsHidden`');
    }
}
