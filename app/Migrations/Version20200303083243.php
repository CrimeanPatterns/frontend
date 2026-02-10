<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200303083243 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table MileValue 
            add TotalSpentInLocalCurrency decimal(10,2) comment 'Потраченная сумма в валюте покупки',
            add LocalCurrency varchar(3) comment 'Валюта покупки'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
