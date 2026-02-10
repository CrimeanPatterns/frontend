<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250704121613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table `UserSignalAttribute` drop foreign key `UserSignalAttribute_ibfk_1`, drop foreign key `UserSignalAttribute_ibfk_2`');
        $this->addSql('alter table `UserSignalAttribute` add foreign key (`UserSignalID`) references UserSignal(`UserSignalID`) on delete cascade');
        $this->addSql('alter table `UserSignalAttribute` add foreign key (`SignalAttributeID`) references SignalAttribute(`SignalAttributeID`) on delete cascade');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
