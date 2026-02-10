<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250320101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` ADD `IsNonAffiliateDisclosure` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Флаг вывода Card offer not available'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `CreditCard` DROP `IsNonAffiliateDisclosure`
        ');
    }
}
