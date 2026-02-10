<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201020061225 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE UserOAuth 
            ADD Email VARCHAR(250) NOT NULL COMMENT 'Email-ящик показывается на странице профиля для разлинковки' AFTER UserID,
            ADD FirstName VARCHAR(250) NOT NULL COMMENT 'Имя показывается на странице профиля для разлинковки' AFTER Email,
            ADD LastName VARCHAR(250) NOT NULL COMMENT 'Фамилия показывается на странице профиля для разлинковки' AFTER FirstName
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE UserOAuth
            DROP Email,
            DROP FirstName,
            DROP LastName
        ");
    }
}
