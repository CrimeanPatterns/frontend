<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200310111111 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE `PromotionCard` TO `PromotionCard_DELETE`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `PromotionCard` (
                `PromotionCardID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `Title` varchar(80) NOT NULL,
                `Description` text,
                `PictureVer` int(11) NOT NULL,
                `PictureExt` varchar(5) NOT NULL,
                `RedirectID` int(11) NOT NULL,
                `CardType` tinyint(1) DEFAULT NULL,
                `VisibleInDetails` tinyint(4) DEFAULT NULL,
                `VisibleInList` tinyint(4) NOT NULL DEFAULT '0',
                `VisibleOnLanding` tinyint(4) NOT NULL DEFAULT '0',
                `SortIndex` int(11) DEFAULT NULL,
                `ProviderID` int(11) DEFAULT NULL,
                `CountryID` int(11) DEFAULT NULL,
                `Promoted` tinyint(1) NOT NULL DEFAULT '0',
                `Text` text NOT NULL,
                `DealID` int(11) DEFAULT NULL,
                `IssuerProviderID` int(11) DEFAULT NULL,
                `CobrandProviderID` int(11) DEFAULT NULL,
                PRIMARY KEY (`PromotionCardID`),
                CONSTRAINT `CobrandProviderID` FOREIGN KEY (`CobrandProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `IssuerProviderID` FOREIGN KEY (`IssuerProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `PromotionCard_ibfk_1` FOREIGN KEY (`RedirectID`) REFERENCES `Redirect` (`RedirectID`),
                CONSTRAINT `PromotionCard_ibfk_2` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
                CONSTRAINT `PromotionCard_ibfk_3` FOREIGN KEY (`CountryID`) REFERENCES `Country` (`CountryID`),
                CONSTRAINT `PromotionCard_ibfk_4` FOREIGN KEY (`DealID`) REFERENCES `Deal` (`DealID`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->addSql("
INSERT INTO `PromotionCard` (`PromotionCardID`, `Title`, `Description`, `PictureVer`, `PictureExt`, `RedirectID`, `CardType`, `VisibleInDetails`, `VisibleInList`, `VisibleOnLanding`, `SortIndex`, `ProviderID`, `CountryID`, `Promoted`, `Text`, `DealID`, `IssuerProviderID`, `CobrandProviderID`) VALUES
(95, 'Southwest Rapid Rewards® Premier Credit Card', NULL, 1548261872, 'png', 192, 0, 0, 1, 0, 24, 16, 230, 0, 'Earn 40,000 bonus points', NULL, 87, 16),
(96, 'British Airways Visa Signature® Card', NULL, 1548972061, 'png', 193, 0, 0, 1, 0, 130, 31, 230, 0, 'Earn up to 100,000 bonus Avios', NULL, NULL, NULL),
(97, 'The World of Hyatt Credit Card', NULL, 1548261966, 'png', 194, 0, 0, 0, 0, 75, 10, 230, 0, 'Up To 50,000 Bonus Points', NULL, NULL, NULL),
(98, 'Barclaycard Arrival® Plus World Elite Mastercard®', NULL, 1510774935, 'png', 195, 0, 0, 0, 0, 20, 123, 230, 0, '70,000 Bonus Miles', NULL, NULL, NULL),
(100, 'The JetBlue Plus Card', NULL, 1513022073, 'png', 196, 0, 0, 0, 0, 100, 13, 230, 0, '30,000 Bonus Points', NULL, NULL, NULL),
(101, 'United℠ TravelBank Card', NULL, 1507632962, 'jpg', 198, 0, 0, 0, 0, 110, 26, 230, 0, '$150 in TravelBank cash', NULL, NULL, NULL),
(102, 'Delta SkyMiles® Gold American Express Card', NULL, 1580411313, 'png', 199, 0, 0, 1, 0, 60, 7, 230, 0, 'Earn up to 70,000 Bonus Miles. Terms Apply.', NULL, 84, 7),
(103, 'Platinum Delta SkyMiles® Credit Card from American Express', NULL, 1580421820, 'png', 201, 0, 0, 1, 0, 15, 7, 230, 0, 'Earn up to 100,000 Bonus Miles. Terms Apply.', NULL, 84, 7),
(104, 'Delta SkyMiles® Gold Business American Express Card', NULL, 1580405750, 'png', 200, 1, 0, 1, 0, 155, 7, 230, 0, 'Earn up to 70,000 Bonus Miles. Terms Apply.', NULL, 84, 7),
(105, 'Delta SkyMiles® Platinum Business American Express Card', NULL, 1580407054, 'png', 202, 1, 0, 1, 0, 120, 7, 230, 0, 'Earn up to 100,000 Bonus Miles. Terms Apply.', NULL, 84, 7),
(106, 'United MileagePlus® Club Card', NULL, 1507669694, 'png', 203, 0, 0, 0, 0, 105, 26, 230, 0, 'Earn 50,000 bonus miles', NULL, NULL, NULL),
(107, 'Chase Sapphire Reserve®', NULL, 1557171195, 'png', 204, 0, 0, 1, 0, 9, 87, 230, 0, 'Earn 50,000 bonus points', NULL, 87, 87),
(108, 'Hilton Honors Business Card from American Express', NULL, 1581011450, 'png', 205, 1, 0, 1, 0, 90, 22, 230, 0, '125,000 Bonus Points. Terms Apply', NULL, 84, 22),
(109, 'Barclays Arrival® Premier World Elite Mastercard®', NULL, 1522933934, 'png', 206, 0, 0, 0, 0, 79, 123, 230, 0, 'unlimited 2x miles', NULL, NULL, NULL),
(110, 'IHG® Rewards Club Premier Credit Card', NULL, 1522957824, 'png', 207, 0, 0, 1, 0, 40, 12, 230, 0, 'Earn 140,000 Bonus Points', NULL, 87, 12),
(111, 'Ink Business Cash℠ Credit Card', NULL, 1523308464, 'jpg', 208, 1, 0, 1, 0, 65, 87, 230, 0, 'Earn $500 bonus cash back', NULL, NULL, NULL),
(112, 'Marriott Rewards® Premier Plus Credit Card', NULL, 1525700634, 'png', 209, 0, 0, 0, 0, 50, 17, 230, 0, 'Earn up to 75,000 Bonus Points', NULL, NULL, NULL),
(113, 'The Business Platinum® Card from American Express OPEN', NULL, 1525959100, 'png', 210, 1, 0, 1, 0, 35, 84, 230, 0, 'Up to 75,000 Membership Rewards® Points. Terms Apply.', NULL, NULL, NULL),
(114, 'SimplyCash® Plus Business Credit Card from American Express', NULL, 1533905109, 'png', 228, 1, 0, 0, 0, 170, 84, 230, 0, '5% cash back on multiple categories on up to $50K in spend/year. Terms Apply.', NULL, NULL, NULL),
(115, 'The Plum Card® from American Express OPEN', NULL, 1562189313, 'png', 227, 1, 0, 1, 0, 180, 84, 230, 0, '1.5% discount when you pay early, with no cap on what you can earn back. Terms Apply.', NULL, NULL, NULL),
(116, 'Blue Cash Everyday® Card from American Express', NULL, 1534242273, 'png', 225, 0, 0, 1, 0, 135, 84, 230, 0, '$150 statement credit. Terms Apply', NULL, NULL, NULL),
(117, 'Blue Cash Preferred® Card from American Express', NULL, 1557766898, 'png', 226, 0, 0, 1, 0, 105, 84, 230, 0, '$250 statement credit. Terms Apply.', NULL, NULL, NULL),
(118, 'American Express Cash Magnet® Card', NULL, 1553191704, 'png', 224, 0, 0, 1, 0, 200, 84, 230, 0, '$150 cash back. Terms Apply', NULL, NULL, NULL),
(119, 'Delta SkyMiles® Reserve Business American Express Card', NULL, 1580408109, 'png', 223, 1, 0, 1, 0, 150, 7, 230, 0, 'Earn up to 100,000 Bonus Miles. Terms Apply.', NULL, 84, 7),
(120, 'Delta SkyMiles® Blue American Express Card', NULL, 1580412024, 'png', 222, 0, 0, 1, 0, 140, 7, 230, 0, 'Earn 15,000 Bonus Miles. Terms Apply.', NULL, 84, 7),
(121, 'Hilton Honors American Express Card', NULL, 1563472641, 'png', 211, 0, 0, 1, 0, 95, 22, 230, 0, '75,000 Bonus Points. Terms Apply', NULL, NULL, NULL),
(122, 'Hilton Honors American Express Surpass® Card', NULL, 1534245681, 'png', 212, 0, 0, 1, 0, 70, 22, 230, 0, '125,000 Bonus Points (Terms Apply)', NULL, NULL, NULL),
(123, 'Business Green Rewards Card from American Express OPEN', NULL, 1534245888, 'png', 221, 1, 0, 1, 0, 175, 84, 230, 0, 'Earn 15,000 Membership Rewards® points. Terms Apply.', NULL, NULL, NULL),
(124, 'The Blue Business® Plus Credit Card from American Express', NULL, 1534246106, 'png', 220, 1, 0, 1, 0, 125, 84, 230, 0, 'Earn 2X Membership Rewards® on the first $50K in purchases per year. Terms Apply.', NULL, NULL, NULL),
(125, 'American Express® Business Gold Card', NULL, 1549043973, 'jpeg', 219, 1, 0, 1, 0, 165, 84, 230, 0, 'Earn 35,000 Membership Rewards® points. Terms Apply.', NULL, NULL, NULL),
(126, 'American Express® Gold Card', NULL, 1559669507, 'png', 218, 0, 0, 1, 0, 280, 84, 230, 0, 'Earn 35,000 Membership Rewards® Points. Terms Apply', NULL, NULL, NULL),
(127, 'The Amex EveryDay® Credit Card from American Express', NULL, 1534248948, 'png', 217, 0, 0, 0, 0, 290, 84, 230, 0, '10,000 Membership Rewards® points &amp; 0% for 15 months', NULL, NULL, NULL),
(128, 'The Platinum Card® from American Express', NULL, 1534249125, 'png', 216, 0, 0, 1, 0, 50, 84, 230, 0, 'Earn 60,000 Bonus Points', NULL, NULL, NULL),
(129, 'Ink Business Unlimited℠ Credit Card', NULL, 1534250946, 'png', 215, 1, 0, 1, 0, 55, 87, 230, 0, '$500 Bonus Cash Back', NULL, NULL, NULL),
(130, 'Wells Fargo Cash Wise Visa® Card', NULL, 1561745769, 'png', 214, 0, 0, 0, 0, 115, 106, 230, 0, 'Earn a $150 cash rewards bonus', NULL, NULL, NULL),
(131, 'Wells Fargo Propel American Express® card', NULL, 1549399868, 'png', 213, 0, 0, 0, 0, 80, 106, 230, 0, 'Earn 30K bonus points', NULL, NULL, NULL),
(132, 'Aer Lingus Visa Signature® Card', NULL, 1534283140, 'png', 1, 0, 0, 0, 0, 340, 184, 230, 0, 'REPLACE ME', NULL, NULL, NULL),
(133, 'Iberia Visa Signature® Card', NULL, 1534283178, 'png', 1, 0, 0, 0, 0, 350, 86, 230, 0, 'REPLACE ME', NULL, NULL, NULL),
(134, 'Delta SkyMiles® Reserve American Express Card', NULL, 1580423570, 'png', 231, 0, 0, 1, 0, 85, 7, 230, 0, 'Earn up to 100,000 Bonus Miles.\r\nTerms Apply.', NULL, 84, 7),
(135, 'Chase Freedom®', NULL, 1560269848, 'png', 232, 0, 0, 1, 0, 100, 87, 230, 0, 'Earn a $150 Bonus', NULL, NULL, NULL),
(136, 'Chase Freedom Unlimited®', NULL, 1560954467, 'png', 233, 0, 0, 1, 0, 25, 87, 230, 0, 'Earn a $150 Bonus', NULL, NULL, NULL),
(137, 'Hilton Honors American Express Aspire Card', NULL, 1534286935, 'png', 1, 0, 0, 0, 0, 390, 22, 230, 0, '150,000 Bonus Points', NULL, NULL, NULL),
(138, 'The Ritz-Carlton Rewards® Credit Card', NULL, 1534287093, 'png', 1, 0, 0, 0, 0, 400, 521, 230, 0, 'REPLACE ME', NULL, NULL, NULL),
(139, 'The Amex EveryDay® Preferred Credit Card from American Express', NULL, 1534287356, 'png', 1, 0, 0, 0, 0, 410, 84, 230, 0, 'REPLACE ME', NULL, NULL, NULL),
(140, 'The Hyatt Credit Card', NULL, 1534287642, 'png', 1, 0, 0, 0, 0, 420, 10, 230, 0, 'REPLACE ME', NULL, NULL, NULL),
(141, 'Southwest Rapid Rewards® Priority Credit Card', NULL, 1542142478, 'png', 229, 0, 0, 1, 0, 110, 16, 230, 0, 'Earn 40,000 bonus points', NULL, 87, 16),
(142, 'United℠ Explorer Business Card', NULL, 1547593323, 'png', 230, 1, 0, 0, 0, 45, 87, 230, 0, 'Earn up to 100,000 bonus miles.', NULL, 87, 26),
(145, 'Marriott Bonvoy Bold™ Credit Card', NULL, 1560268361, 'png', 236, 0, 0, 1, 0, 120, 87, 230, 0, 'Earn 50,000 Bonus Points', NULL, 87, 17),
(146, 'Southwest Rapid Rewards® Performance Business Credit Card', NULL, 1561592764, 'png', 237, 1, NULL, 1, 0, 22, 16, 230, 0, 'Earn up to 100,000 bonus points', NULL, 87, 16),
(147, 'American Express® Blue Business Cash Card', NULL, 1573150144, 'png', 238, 1, NULL, 1, 0, 200, 84, NULL, 0, 'Earn 2% cash back on the first $50K in purchases per year. Terms Apply.', NULL, NULL, NULL),
(148, 'IHG® Rewards Club Traveler Credit Card', NULL, 1571964857, 'png', 239, 0, NULL, 1, 0, 450, 12, 230, 0, 'Earn 60,000 bonus points', NULL, 87, 12),
(149, 'United℠ Business Card', NULL, 1579823235, 'png', 240, 1, NULL, 1, 0, 460, 87, NULL, 0, 'Earn 100,000 bonus miles.', NULL, NULL, NULL);
        ");
    }
}
