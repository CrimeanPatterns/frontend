<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190517031734 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Reservation 
            add CancellationDeadline datetime comment 'Deadline date for changing or cancelling the reservation without additional penalties',
            drop ExtPropertyMerged");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Reservation drop CancellationDeadline");
    }
}
