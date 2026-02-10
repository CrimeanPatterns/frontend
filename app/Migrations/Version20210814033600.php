<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210814033600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table ShoppingCategory add LinkedToGroupBy tinyint comment 'Как категория привязана к группе, смотри константы ShoppingCategory::LINKED_TO_GROUP_BY_' after ShoppingCategoryGroupID");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table ShoppingCategory drop LinkedToGroupBy");
    }
}
