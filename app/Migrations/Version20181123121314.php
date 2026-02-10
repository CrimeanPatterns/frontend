<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20181123121314 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Usr` DROP `AAUCredits`");
        $this->addSql("ALTER TABLE `Account` DROP `AcceleratedUpdateStartDate`");
        //$this->addSql("ALTER TABLE `Account` DROP INDEX `AcceleratedUpdateStartDate`");
        $this->addSql("DROP TABLE `AAUCreditsTransaction`");
    }

    public function down(Schema $schema): void
    {
    }
}
