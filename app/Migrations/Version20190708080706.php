<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190708080706 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `UserPointValue` (
                `UserPointValueID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `UserID` int(11) NOT NULL,
                `ProviderID` int(11) NOT NULL,
                `Value` decimal(10,4) NOT NULL COMMENT 'Значение введенное пользователем',
                `UpdateDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`UserPointValueID`),
                UNIQUE KEY `ukeyUsrProvider` (`UserID`,`ProviderID`),
                CONSTRAINT `fkUserID` FOREIGN KEY (`UserID`) REFERENCES `Usr` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fkProviderID` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE `PointEssentialProgram` (
              `PointEssentialProgramID` int(11) NOT NULL AUTO_INCREMENT,
              `ProviderID` int(11) NOT NULL,
              `Program` char(5) DEFAULT NULL,
              `Alliance` tinyint(2) DEFAULT NULL COMMENT 'PointEssentialProgram::ALLIANCE',
              `WithAmex` tinyint(1) DEFAULT NULL,
              `WithChase` tinyint(1) DEFAULT NULL,
              `WithCiti` tinyint(1) DEFAULT NULL,
              `WithCapitalOne` tinyint(1) DEFAULT NULL,
              `WithMarriott` tinyint(1) DEFAULT NULL,
              PRIMARY KEY (`PointEssentialProgramID`),
              KEY `fkeProviderID` (`ProviderID`),
              CONSTRAINT `fkeProviderID` FOREIGN KEY (`ProviderID`) REFERENCES `Provider` (`ProviderID`) ON DELETE CASCADE ON UPDATE CASCADE
            ) 
        ");

        $this->addSql("
            INSERT INTO `PointEssentialProgram` (`PointEssentialProgramID`, `ProviderID`, `Program`, `Alliance`, `WithAmex`, `WithChase`, `WithCiti`, `WithCapitalOne`, `WithMarriott`) VALUES
            (1, 186, '*A3', 1, 0, 0, 0, 0, 1),
            (2, 184, '*EI', 2, 1, 1, 0, 0, 0),
            (3, 52, '*SU', 3, 0, 0, 0, 0, 1),
            (4, 390, '*AR', 3, 0, 0, 0, 0, 0),
            (5, 96, '*AM', 3, 1, 0, 0, 1, 1),
            (6, 2, '*AC', 1, 1, 0, 0, 1, 1),
            (7, 208, '*CA', 1, 0, 0, 0, 0, 1),
            (8, 1389, '*UX', 3, 0, 0, 0, 0, 0),
            (9, 44, '*AF', 3, 1, 1, 1, 1, 1),
            (10, 152, '*AI', 1, 0, 0, 0, 0, 0),
            (11, 258, '*NZ', 1, 0, 0, 0, 0, 1),
            (12, 223, '*AZ', 3, 1, 0, 0, 1, 1),
            (13, 92, '*NH', 1, 1, 0, 0, 0, 1),
            (14, 1, '*AA', 4, 0, 0, 0, 0, 1),
            (15, 78, '*OZ', 1, 0, 0, 0, 0, 1),
            (16, 537, '*O6', 1, 0, 0, 0, 0, 0),
            (17, 416, '*AV', 1, 1, 0, 1, 1, 1),
            (18, 31, '*BA', 4, 1, 1, 0, 0, 1),
            (19, 768, '*SN', 1, 0, 0, 0, 0, 0),
            (20, 35, '*CX', 4, 1, 0, 1, 1, 1),
            (21, 95, '*CI', 3, 0, 0, 0, 0, 0),
            (22, 181, '*MU', 3, 0, 0, 0, 0, 1),
            (23, 274, '*CZ', 3, 0, 0, 0, 0, 1),
            (24, 1103, '*CM', 1, 0, 0, 0, 0, 1),
            (25, 127, '*OK', 3, 0, 0, 0, 0, 0),
            (26, 7, '*DL', 3, 1, 0, 0, 0, 1),
            (27, 276, '*MS', 1, 0, 0, 0, 0, 0),
            (28, 66, '*LY', 2, 1, 0, 0, 0, 0),
            (29, 48, '*EK', 2, 1, 0, 0, 1, 1),
            (30, 449, '*ET', 1, 0, 0, 0, 0, 0),
            (31, 179, '*EY', 2, 1, 0, 1, 1, 1),
            (32, 79, '*BR', 1, 0, 0, 1, 1, 0),
            (33, 178, '*AY', 4, 0, 0, 0, 1, 0),
            (34, 20, '*HA', 2, 1, 1, 0, 0, 0),
            (35, 86, '*IB', 4, 1, 0, 0, 0, 1),
            (36, 43, '*JL', 4, 0, 0, 0, 0, 1),
            (37, 13, '*JB', 2, 1, 1, 1, 0, 1),
            (38, 34, '*KE', 3, 0, 0, 0, 0, 1),
            (39, 134, '*LA', 4, 0, 0, 0, 0, 1),
            (40, 39, '*LH', 1, 0, 0, 0, 0, 1),
            (41, 136, '*MH', 4, 0, 0, 1, 0, 0),
            (42, 560, '*ME', 3, 0, 0, 0, 0, 0),
            (43, 33, '*QF', 4, 0, 0, 1, 1, 1),
            (44, 294, '*RJ', 4, 0, 0, 0, 0, 0),
            (45, 155, '*S7', 4, 0, 0, 0, 0, 0),
            (46, 85, '*SK', 1, 0, 0, 0, 0, 0),
            (47, 265, '*SV', 3, 0, 0, 0, 0, 1),
            (48, 71, '*SQ', 1, 1, 1, 1, 1, 1),
            (49, 157, '*SA', 1, 0, 0, 0, 0, 1),
            (50, 520, '*UL', 4, 0, 0, 0, 0, 0),
            (51, 389, '*JJ', 4, 0, 0, 0, 0, 0),
            (52, 176, '*TP', 1, 0, 0, 0, 0, 1),
            (53, 41, '*TG', 1, 0, 0, 1, 0, 1),
            (54, 97, '*TK', 1, 0, 0, 1, 0, 1),
            (55, 26, '*UA', 1, 0, 1, 0, 0, 1),
            (56, 348, '*VN', 3, 0, 0, 0, 0, 0),
            (57, 40, '*VS', 2, 1, 1, 1, 0, 1),
            (58, 1275, '*MF', 3, 0, 0, 0, 0, 0),
            (59, 16, '*WN', 2, 0, 1, 0, 0, 1),
            (60, 553, '*GA', 3, 0, 0, 1, 0, 0),
            (61, 51, '*9W', 2, 0, 0, 1, 0, 1),
            (62, 83, '*QR', 4, 0, 0, 1, 1, 1),
            (63, 275, '*HU', 2, 0, 0, 0, 1, 1),
            (64, 18, '*AS', 2, 0, 0, 0, 0, 1),
            (65, 9, '*F9', 2, 0, 0, 0, 0, 1),
            (66, 93, '*VA', 2, 0, 0, 0, 0, 1)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE `UserPointValue`");
        $this->addSql("DROP TABLE `PointEssentialProgram`");
    }
}
