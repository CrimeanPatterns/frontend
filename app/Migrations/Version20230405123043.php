<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230405123043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        return; // locks table on prod
        $exists = $this->connection->executeQuery("select * from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME = 'Usr' and COLUMN_NAME = 'StripeCustomerID'")->fetchOne();
        if (!$exists) {
            $this->addSql("alter table Usr add StripeCustomerID varchar(64) comment 'id пользователя stripe (оплата кредитками)'");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
