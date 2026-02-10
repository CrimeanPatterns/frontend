<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190412085836 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Location add IsGenerated int default 0 not null comment '0 - добавлена пользователм, 1 - локация была доавлена сервером автоматически'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
