<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141118052505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('CouponTarget')) {
            $schema->dropTable('CouponTarget');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `CouponTarget` (
          `CouponTargetID` int(11) NOT NULL AUTO_INCREMENT,
          `CouponID` int(11) NOT NULL,
          `TypeID` int(11) NOT NULL,
          `CategoryID` int(11) DEFAULT NULL,
          PRIMARY KEY (`CouponTargetID`),
          KEY `AK_Key_2` (`CouponID`,`TypeID`,`CategoryID`),
          CONSTRAINT `fkCouponTarget_ref_Coupon` FOREIGN KEY (`CouponID`) REFERENCES `Coupon` (`CouponID`)
        ) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
        ");
    }
}
