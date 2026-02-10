<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250425083816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("create table ReceivedTotal(
            ReceivedTotalID int not null auto_increment,
            UserID int not null,
            ExtensionVersion int not null,
            URL varchar(1000) not null,
            ReceiveDate datetime not null,
            Total decimal(8,2) not null,
            primary key (ReceivedTotalID),
            foreign key (UserID) references Usr(UserID) on delete cascade
        ) engine=InnoDB comment 'user purchases, reported by extension'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
