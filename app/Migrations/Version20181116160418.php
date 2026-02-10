<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181116160418 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        //This migration was supposed to be removed, but since it's already in the database it's absence generates a warning. Hence this dud that does nothing.
    }

    public function down(Schema $schema): void
    {
    }
}
