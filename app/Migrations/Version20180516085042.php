<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180516085042 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE DiffChange SET Property = 'DepartureGate' WHERE Property = 'Gate'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE DiffChange SET Property = 'Gate' WHERE Property = 'DepartureGate'");
    }
}
