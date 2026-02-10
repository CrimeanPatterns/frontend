<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200203190000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE QsTransaction SET Clicks = 1 WHERE ProcessDate IS NOT NULL AND Clicks = 0');
    }

    public function down(Schema $schema): void
    {
    }
}
