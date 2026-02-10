<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190204083705 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard`
            ADD `PointValue` decimal(4,2) NOT NULL DEFAULT 0.01 COMMENT 'Стоимость 1 заработанного поинта в $' AFTER `Description`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard`
            DROP `PointValue`;
        ");
    }
}
