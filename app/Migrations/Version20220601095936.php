<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220601095936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table Merchant 
            add ShoppingCategoryGroupID int,
            algorithm INSTANT"
        );
        $this->addSql("alter table Merchant 
            add foreign key fkShoppingCategoryGroupID (ShoppingCategoryGroupID) references ShoppingCategoryGroup(ShoppingCategoryGroupID)  on delete set null"
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
