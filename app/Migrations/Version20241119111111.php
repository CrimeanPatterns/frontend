<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241119111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `ProviderCouponType` (
                `ProviderCouponTypeID` smallint NOT NULL AUTO_INCREMENT,
                `TypeID` tinyint NOT NULL,
                `BlogIdsMileExpiration` varchar(256) DEFAULT NULL COMMENT 'Blog ID that talks about point or mile expiration policy for this provider, if more than one, make them comma-separated but the first one will be used in most cases',
                PRIMARY KEY (`ProviderCouponTypeID`),
                UNIQUE KEY `TypeID` (`TypeID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Связка с ProviderCoupon.TypeID для общих параметров для всех документов/купонов/карт/сертификатов'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            DROP TABLE `ProviderCouponType`
        ');
    }
}
