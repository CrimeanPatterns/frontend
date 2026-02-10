<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220602101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $reservations = [
            4324104 => 'No. 4 Zhongshan 1st Road, Xinxing District, Kaohsiung, Taiwan',
            4890013 => 'No. 69, Zhongxing Rd., West District , Chiayi County, Taiwan',
            4318058 => '1-13-11 Nanko-Kita, Suminoe-Ku Osaka, Japan, 559-0034',
            4812217 => '12005 Regency Village Drive, Orlando, Florida, 32821, United States',
            4801556 => '6985 Sea Harbor Drive Orlando, Florida, 32821, United States',
        ];

        foreach ($reservations as $id => $address) {
            $this->addSql("update Reservation set Address='" . $address . "' where ReservationID=" . $id);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
