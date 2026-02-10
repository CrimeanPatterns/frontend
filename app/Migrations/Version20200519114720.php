<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200519114720 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            update CreditCardShoppingCategoryGroup 
            set EndDate = DATE_ADD(StartDate, INTERVAL 3 MONTH)
            where StartDate is not null
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
