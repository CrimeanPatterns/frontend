<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150529091706 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `BonusConversionProvider` (
                `BonusConversionProviderID` INT(11) NOT NULL AUTO_INCREMENT,

                `ProviderID` INT(11) COMMENT 'Ссылка на провайдера',
                `Enabled` TINYINT(1) NOT NULL COMMENT 'Состояние конвертации для этого провайдера',
                `Name` VARCHAR(100) NOT NULL COMMENT 'Название провайдера для надписи',
                `PurchaseLink` VARCHAR(2000) DEFAULT NULL COMMENT 'Ссылка на покупку миль',

                `MinMiles` INT(11) NOT NULL COMMENT 'Мин. количество миль доступных для конвертации',
                `MaxMiles` INT(11) NOT NULL COMMENT 'Макс. количество миль доступных для конвертации',
                `StepMiles` INT(11) NOT NULL COMMENT 'Шаг в милях для расчетов',
                `OneMileCost` DECIMAL(10, 5) NOT NULL COMMENT 'Стоимость одной мили в долларах',
                `FixedFee` DECIMAL(10, 2) NOT NULL COMMENT 'Сколько с каждой покупки удерживается в долларах',

                PRIMARY KEY (`BonusConversionProviderID`),
                UNIQUE KEY `idx_BonusConversionProvider_ProviderID` (`ProviderID`),
                CONSTRAINT `BonusConversionProvider_ibfk_1` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT = 'Провайдеры для конвертации бонусов';"
        );

        $providers = [
            [
                'ProviderID' => 7,
                'Enabled' => 1,
                'Name' => 'Delta',

                'MinMiles' => 2000,
                'MaxMiles' => 200000,
                'StepMiles' => 2000,
                'OneMileCost' => '0.037625', // USD
                'FixedFee' => '0.00', // USD
            ],
            [
                'ProviderID' => 1,
                'Enabled' => 1,
                'Name' => 'American',
                'PurchaseLink' => 'https://buymiles.aa.com/en/buygift/info',

                'MinMiles' => 1000,
                'StepMiles' => 1000,
                'MaxMiles' => 100000,
                'OneMileCost' => '0.03171', // USD
                'FixedFee' => '35.00', // USD
            ],
            [
                'ProviderID' => 22,
                'Enabled' => 0,
                'Name' => 'Hilton',
                'PurchaseLink' => 'https://secure3.hilton.com/en/hh/customer/login/index.htm?forwardPageURI=/en/hh/customer/account/purchase.htm&stop_mobi=yes',

                'MinMiles' => 1000,
                'StepMiles' => 1000,
                'MaxMiles' => 80000,
                'OneMileCost' => '0.01', // USD
                'FixedFee' => '0.0', // USD
            ],
            [
                'ProviderID' => 17,
                'Enabled' => 0,
                'Name' => 'Marriott',
                'PurchaseLink' => 'https://buy.points.com/PointsPartnerFrames/partners/Marriott/container.html?language=EN&product=BUY',

                'MinMiles' => 1000,
                'StepMiles' => 1000,
                'MaxMiles' => 50000,
                'OneMileCost' => '0.0125', // USD
                'FixedFee' => '0.0', // USD
            ],
            [
                'ProviderID' => 25,
                'Enabled' => 0,
                'Name' => 'Starwood',
                'PurchaseLink' => 'https://buy.points.com/PointsPartnerFrames/partners/spg/container.html?language=en&product=BUY&CAMPAIGNCODE=SWAccSumm&c=SWAccSumm',

                'MinMiles' => 500,
                'StepMiles' => 500,
                'MaxMiles' => 20000,
                'OneMileCost' => '0.035', // USD
                'FixedFee' => '0.0', // USD
            ],
            [
                'ProviderID' => 12,
                'Enabled' => 0,
                'Name' => 'IHG',
                'PurchaseLink' => 'https://buy.points.com/PointsPartnerFrames/partners/ihg/container.html?product=buy',

                'MinMiles' => 1000,
                'StepMiles' => 500,
                'MaxMiles' => 60000,
                'OneMileCost' => '0.0135', // USD
                'FixedFee' => '0.0', // USD
            ],
            [
                'ProviderID' => 10,
                'Enabled' => 0,
                'Name' => 'Hyatt',
                'PurchaseLink' => 'https://buy.points.com/PointsPartnerFrames/partners/hyatt/container.html?language=EN&product=BUY&CAMPAIGNCODE=Hyongoingcombinexfercopylink',

                'MinMiles' => 1000,
                'StepMiles' => 1000,
                'MaxMiles' => 55000,
                'OneMileCost' => '0.024', // USD
                'FixedFee' => '0.0', // USD
            ],
            [
                'ProviderID' => 16,
                'Enabled' => 0,
                'Name' => 'Southwest',
                'PurchaseLink' => 'https://www.southwest.com/flight/login?returnUrl=%2Faccount%2Frapidrewards%2Fpoints%2Fbuy-points',

                'MinMiles' => 2000,
                'StepMiles' => 500,
                'MaxMiles' => 60000,
                'OneMileCost' => '0.0275', // USD
                'FixedFee' => '0.0', // USD
            ],
            [
                'ProviderID' => 13,
                'Enabled' => 0,
                'Name' => 'JetBlue Airways',
                'PurchaseLink' => 'https://trueblue.jetblue.com/group/trueblue/points-buy',

                'MinMiles' => 1000,
                'StepMiles' => 500,
                'MaxMiles' => 30000,
                'OneMileCost' => '0.03763', // USD
                'FixedFee' => '0.0', // USD
            ],
            [
                'ProviderID' => 31,
                'Enabled' => 0,
                'Name' => 'British Airways (DISABLED!!!)',
                'PurchaseLink' => 'https://www.britishairways.com/travel/purchase-avios/public/en_us/execclub?eId=115002&purchaseType=Buy',

                'MinMiles' => 1000,
                'StepMiles' => 500,
                'MaxMiles' => 35000,
                'OneMileCost' => '0.053', // USD
                'FixedFee' => '0.0', // USD
            ],
        ];

        foreach ($providers as $provider) {
            $fieldsSql = implode(', ', array_map(function ($str) { return "`$str`"; }, array_keys($provider)));
            $valuesSql = implode(', ', array_map(function ($el) { return is_int($el) ? $el : "'$el'"; }, array_values($provider)));
            $this->addSql("INSERT INTO `BonusConversionProvider`($fieldsSql) VALUES($valuesSql)");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `BonusConversionProvider`');
    }
}
