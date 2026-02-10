<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200922091957 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE UserOAuth ADD AvatarURL VARCHAR(250) DEFAULT NULL AFTER OAuthID");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE UserOAuth DROP AvatarURL");
    }
}
