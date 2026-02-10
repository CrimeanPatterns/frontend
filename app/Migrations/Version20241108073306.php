<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241108073306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr 
            add FirstSubscriptionCartItemID int, 
            add LastSubscriptionCartItemID int,
            add SubscriptionCartItemTypeID tinyint comment 'see CartItem.TypeID field',
            add SubscriptionPrice decimal(10,2) comment 'how many usd we charge per period',
            add SubscriptionPeriod smallint comment 'how many days in period. Only constants from SubscriptionPeriod::DURATION_DAYS'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
