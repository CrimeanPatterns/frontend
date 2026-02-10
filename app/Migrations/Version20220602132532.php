<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220602132532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table Merchant
            add NotNullGroupID int GENERATED ALWAYS AS (if((`ShoppingCategoryGroupID` is null), 0, `ShoppingCategoryGroupID`)) VIRTUAL,
            ALGORITHM = INSTANT            
        ");
        $this->addSql("alter table Merchant
            add unique key akNameNotNullGroupID(Name, NotNullGroupID)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Merchant
            drop key akNameNotNullGroupID,
            drop NotNullGroupID
        ");
    }
}
