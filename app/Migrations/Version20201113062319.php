<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201113062319 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserOAuth add DeclinedMailboxAccess tinyint not null default 0 comment 'Пользователь удалил подключенный мэйлбокс, больше не надо его подключать при oauth signin'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
