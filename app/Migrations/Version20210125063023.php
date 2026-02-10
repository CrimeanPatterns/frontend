<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125063023 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table RewardsPrice
            drop foreign key RewardsPrice_ibfk_1,
            drop ProviderID");
        $this->addSql("alter table AwardChart 
            add ProviderID int null comment 'провайдер чьи мили будут тратиться (TODO: сделать not null после заполнения)' after Name,
            add FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE")
        ;
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
