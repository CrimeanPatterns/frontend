<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220401010101 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `CreditCardShoppingCategoryGroup` DROP `ShowExpiredCategories`');
    }

    public function down(Schema $schema): void
    {
    }
}
