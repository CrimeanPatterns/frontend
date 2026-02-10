<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221221075655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table RetailProviderMerchant add unique key `Provider` (`ProviderID`)');
        $this->addSql('alter table RetailProviderMerchant drop key `ProviderMerchant`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `RetailProviderMerchant` ADD UNIQUE KEY `ProviderMerchant` (`ProviderID`, `MerchantID`)');
        $this->addSql('alter table RetailProviderMerchant drop key `Provider`');
    }
}
