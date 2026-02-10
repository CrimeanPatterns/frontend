<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230331095650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('Usr')->hasIndex('Usr_IsResidentPointSet')) {
            $this->addSql('alter table Usr add index `Usr_IsResidentPointSet` (`IsResidentPointSet`)');
        }

        if (!$schema->getTable('Usr')->hasIndex('Usr_LastLogonPoint')) {
            $this->addSql("alter table Usr ADD SPATIAL INDEX `Usr_LastLogonPoint` (`LastLogonPoint`)");
        }

        if (!$schema->getTable('Usr')->hasIndex('Usr_ResidentPoint')) {
            $this->addSql("alter table Usr ADD SPATIAL INDEX `Usr_ResidentPoint` (`ResidentPoint`)");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table Usr DROP INDEX `Usr_LastLogonPoint`');
        $this->addSql('alter table Usr DROP INDEX `Usr_ResidentPoint`');
    }
}
