<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241105103512 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Account DROP COLUMN RenewNote, DROP COLUMN RenewProperties;
            ALTER TABLE SubAccount DROP COLUMN RenewNote, DROP COLUMN RenewProperties;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Account ADD COLUMN RenewNote TEXT DEFAULT NULL, ADD COLUMN RenewProperties TEXT DEFAULT NULL;
            ALTER TABLE SubAccount ADD COLUMN RenewNote TEXT DEFAULT NULL, ADD COLUMN RenewProperties TEXT DEFAULT NULL;
        ');
    }
}
