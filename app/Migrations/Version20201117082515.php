<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201117082515 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table UserOAuth modify LastName varchar(250) COMMENT 'Фамилия показывается на странице профиля для разлинковки'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
