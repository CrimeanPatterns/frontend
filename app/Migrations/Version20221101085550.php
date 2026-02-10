<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221101085550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table RetailProviderMerchant add Disabled tinyint not null default 0 COMMENT 'Если отключен руками'");

    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table RetailProviderMerchant drop Disabled');
    }
}
