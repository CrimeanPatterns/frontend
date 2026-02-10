<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181203031731 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `ProviderCoupon` MODIFY `CardNumber` varchar(64)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `ProviderCoupon` MODIFY `CardNumber` varchar(64) NOT NULL");
    }
}
