<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190311052327 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table CouponItem(
            CouponItemID int not null auto_increment,
            CouponID int not null,
            CartItemType tinyint not null,
            Cnt tinyint not null default 1,
            foreign key (CouponID) references Coupon(CouponID) on delete cascade,
            unique key (CouponID, CartItemType),
            primary key (CouponItemID)
        ) engine=InnoDb");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
