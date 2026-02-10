<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140716134155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("update AbTransaction t set t.Title = (select i.ServiceName from AbRequest r left join AbBookerInfo i on r.BookerUserID = i.UserID where r.BookingTransactionID = t.AbTransactionID group by r.BookingTransactionID);");
    }

    public function down(Schema $schema): void
    {
    }
}
