<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190719083918 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE ShoppingCategoryGroup DROP ParentID");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE ShoppingCategoryGroup ADD COLUMN ParentID int(11) DEFAULT NULL AFTER ShoppingCategoryGroupID"
        );
    }
}
