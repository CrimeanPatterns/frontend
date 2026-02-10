<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211220202020 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $data = [
            7833 => ['+1 765-481-2920'],
            7886 => ['+1 918-541-1500'],
            8086 => ['+1 618-346-4400'],
            8089 => ['+1 740-282-9800'],
            8958 => ['+1 616-285-7100'],
            9009 => ['+1 770-448-4663'],
            9579 => ['+86 21 2321 6888'],
            10875 => ['+86 887 822 2233'],
            11074 => ['+86 10 5926 9688', 'https://www.ihg.com/holidayinnexpress/hotels/us/en/beijing/pegwj/hoteldetail'],
            12590 => ['+86 991 699 9999', 'https://www.hilton.com/en/hotels/urccici-conrad-urumqi/'],
            12642 => ['+1 954-943-2525', 'https://www.hilton.com/en/hotels/fllprht-home2-suites-pompano-beach-pier'],
            12660 => ['+1 813-284-0568', 'https://www.hilton.com/en/hotels/tpawaht-home2-suites-tampa-westshore-airport'],
        ];

        foreach ($data as $id => $row) {
            if (1 === count($row)) {
                $this->addSql("UPDATE Hotel SET Phones='" . $row[0] . "' WHERE HotelID = " . $id);
            } elseif (2 === count($row)) {
                $this->addSql("UPDATE Hotel SET Phones='" . $row[0] . "', Website='" . $row[1] . "' WHERE HotelID = " . $id);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
