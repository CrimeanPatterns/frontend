<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221115063225 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE ProviderProperty ADD `Type` TINYINT NULL DEFAULT NULL COMMENT 'Как форматировать свойство, Providerproperty::TYPE_* constants' AFTER Kind
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ProviderProperty DROP `Type`');
    }
}
