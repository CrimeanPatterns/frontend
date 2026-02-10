<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210909142847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("create table ShoppingCategoryGroupChildren(
            ShoppingCategoryGroupChildrenID int not null auto_increment,
            ParentGroupID int not null comment 'Родительская группа',
            ChildGroupID int not null comment 'Дочерняя группа',
            primary key (ShoppingCategoryGroupChildrenID),
            foreign key (ParentGroupID) references ShoppingCategoryGroup(ShoppingCategoryGroupID) on delete cascade,
            foreign key (ChildGroupID) references ShoppingCategoryGroup(ShoppingCategoryGroupID) on delete cascade,
            unique key (ParentGroupID, ChildGroupID)
        ) engine InnoDB comment 'Дочерние группы, например для gas stations & restaurtns будут соответсвенно две дочерние группы. Используется в MerchantMatcher'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
