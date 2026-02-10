<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190610105120 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('delete from `InviteCode` where `Email` is null');
        $this->addSql('alter table `InviteCode` modify `Email` varchar(80) not null');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `InviteCode` modify `Email` varchar(80) null');
    }
}
