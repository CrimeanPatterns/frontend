<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181023111723 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` ADD `IsDisabled` tinyint(4) NOT NULL DEFAULT 0;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `CreditCard` DROP `IsDisabled`;");
    }
}
