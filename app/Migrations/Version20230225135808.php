<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230225135808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-10 23:49:54'  WHERE Provider = 'asia' AND Carrier = 'NU'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-14 22:19:51'  WHERE Provider = 'eurobonus' AND Carrier = 'WF'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-06 23:54:26'  WHERE Provider = 'turkish' AND Carrier = 'ET'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-11 08:39:10'  WHERE Provider = 'israel' AND Carrier = 'TP'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-06 14:43:12'  WHERE Provider = 'delta' AND Carrier = 'FM'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-07 07:39:18'  WHERE Provider = 'delta' AND Carrier = 'MF'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-16 02:26:57'  WHERE Provider = 'etihad' AND Carrier = 'AY'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-15 01:00:05'  WHERE Provider = 'etihad' AND Carrier = 'EW'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-07 05:09:52'  WHERE Provider = 'etihad' AND Carrier = 'NZ'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-10 11:23:12'  WHERE Provider = 'etihad' AND Carrier = 'LX'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2023-01-04 23:35:52'  WHERE Provider = 'etihad' AND Carrier = 'OS'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-06 15:54:04'  WHERE Provider = 'etihad' AND Carrier = 'SK'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-09 17:43:33'  WHERE Provider = 'etihad' AND Carrier = 'UX'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-15 01:14:19'  WHERE Provider = 'mileageplus' AND Carrier = 'OC'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2023-02-17 08:24:21'  WHERE Provider = 'qantas' AND Carrier = '4Z'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-09 06:32:06'  WHERE Provider = 'qantas' AND Carrier = 'EI'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-18 00:43:16'  WHERE Provider = 'qantas' AND Carrier = 'SK'");
        $this->addSql("UPDATE RAFlightStat SET FirstSeen = '2022-12-17 20:40:18'  WHERE Provider = 'qantas' AND Carrier = 'SQ'");

        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('etihad', 'WF', '2023-02-03 23:49:47', '2023-02-03 23:49:47')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2023-02-03 23:49:47'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('etihad', 'WY', '2022-12-07 04:39:12', '2022-12-07 04:39:12')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2022-12-07 04:39:12'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('etihad', 'AS', '2023-02-15 04:15:24', '2023-02-15 04:15:24')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2023-02-15 04:15:24'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('etihad', 'OK', '2022-12-21 21:54:39', '2022-12-21 21:54:39')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2022-12-21 21:54:39'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('israel', 'IB', '2023-01-04 02:15:23', '2023-01-04 02:15:23')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2023-01-04 02:15:23'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('mileageplus', 'DJ', '2023-01-10 07:19:41', '2023-01-10 07:19:41')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2023-01-10 07:19:41'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('qantas', 'HX', '2022-12-17 04:12:14', '2022-12-17 04:12:14')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2022-12-17 04:12:14'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('qantas', 'SB', '2023-02-14 12:18:50', '2023-02-14 12:18:50')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2023-02-14 12:18:50'");
        $this->addSql("INSERT INTO RAFlightStat (Provider, Carrier, FirstSeen, LastSeen) VALUES ('qantas', 'SN', '2022-12-09 14:44:35', '2022-12-09 14:44:35')
                    ON DUPLICATE KEY UPDATE FirstSeen = '2022-12-09 14:44:35'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
