<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230414075141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('Usr')->hasIndex('Usr_LastLogonPoint')) {
            $this->addSql('alter table Usr drop index Usr_LastLogonPoint');
        }

        if ($schema->getTable('Usr')->hasIndex('Usr_ResidentPoint')) {
            $this->addSql('alter table Usr drop index Usr_ResidentPoint');
        }

        if ($schema->getTable('UserIP')->hasIndex('UserIP_Point')) {
            $this->addSql('alter table UserIP drop index UserIP_Point');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
