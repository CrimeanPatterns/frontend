<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221025105649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $this->addSql("
            CREATE TABLE RetailProviderMerchant (
                RetailProviderMerchantID bigint not null auto_increment,
                MerchantID INT NOT NULL COMMENT 'Привязка к мерчанту',
                Manual tinyint not null default 0 COMMENT 'Если добавлен руками',
                Auto tinyint not null default 0 COMMENT 'Если добавлен автоматически',
                ProviderID INT(11) NOT NULL COMMENT 'Привязка к провайдеру',
                PRIMARY KEY (RetailProviderMerchantID),
                UNIQUE KEY (ProviderID, MerchantID),
                FOREIGN KEY (ProviderID) REFERENCES Provider (ProviderID) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (MerchantID) REFERENCES Merchant (MerchantID) ON DELETE CASCADE ON UPDATE CASCADE 
            ) ENGINE=InnoDB COMMENT 'Привязка мерчантов к ретейл провайдеру';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('drop table RetailProviderMerchant');
    }
}
