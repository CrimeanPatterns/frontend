<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Providercoupon;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230921101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ProviderCoupon` CHANGE `TypeID` `TypeID` TINYINT NULL DEFAULT NULL');
        $this->addSql("ALTER TABLE `ProviderCoupon` ADD `TypeName` VARCHAR(64) NULL DEFAULT NULL AFTER `TypeID`, ALGORITHM INSTANT");

        $types = [
            Providercoupon::TYPE_CERTIFICATE => 'Certificate',
            Providercoupon::TYPE_TICKET_COMPANION => 'Companion Ticket',
            Providercoupon::TYPE_COUPON => 'Coupon',
            Providercoupon::TYPE_GIFT_CARD => 'Gift Card',
            Providercoupon::TYPE_STORE_CREDIT => 'Store Credit',
            Providercoupon::TYPE_TICKET => 'Ticket',
            Providercoupon::TYPE_TRAVEL_VOUCHER => 'Travel Voucher',
            Providercoupon::TYPE_INSURANCE_CARD => 'Insurance Card',
            Providercoupon::TYPE_VISA => 'Visa',
            Providercoupon::TYPE_DRIVERS_LICENSE => 'Drivers License',
            Providercoupon::TYPE_PRIORITY_PASS => 'Priority Pass',
        ];

        foreach ($types as $typeId => $typeName) {
            $this->addSql("UPDATE `ProviderCoupon` SET TypeName='" . $typeName . "' WHERE TypeID = " . $typeId . " AND (TypeName IS NULL OR TypeName = '')");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `ProviderCoupon` DROP `TypeName`');
    }
}
