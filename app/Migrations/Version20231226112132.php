<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231226112132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table `MerchantPattern` add column `DescriptionExamples` varchar(512) comment 'Примеры для проверки паттернов при редактировании в схеме' after `Patterns`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `MerchantPattern` drop column `DescriptionExamples`');
    }
}
