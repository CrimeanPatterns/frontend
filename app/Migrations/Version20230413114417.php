<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230413114417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        return;
        if ($schema->getTable('Usr')->hasIndex('Usr_LastLogonPoint')) {
            $this->addSql('DROP INDEX Usr_LastLogonPoint ON Usr');
        }

        if ($schema->getTable('Usr')->hasIndex('Usr_ResidentPoint')) {
            $this->addSql('DROP INDEX Usr_ResidentPoint ON Usr');
        }

        if ($schema->getTable('UserIP')->hasIndex('UserIP_Point')) {
            $this->addSql('DROP INDEX UserIP_Point ON UserIP');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
