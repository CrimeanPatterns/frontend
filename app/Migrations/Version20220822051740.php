<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220822051740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table MobileDevice add column `UserAgent` varchar(256) default null comment 'User-Agent устройства'");

    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table MobileDevice drop column `UserAgent`');
    }
}
