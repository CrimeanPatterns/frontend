<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Entity\Itinerary;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210114115658 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        foreach (Itinerary::$table as $table) {
            $this->addSql("alter table {$table} add FirstSeenDate datetime default current_timestamp() comment 'Когда резервация была первый раз собрана. Может отличаться от CreateDate, в случае если собрано старое письмо с резервацией - FirstSeenDate будет равна дате отправки письма, а CreateDate - дате обработки письма'");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (Itinerary::$table as $table) {
            $this->addSql("alter table {$table} drop FirstSeenDate");
        }
    }
}
