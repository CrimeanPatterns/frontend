<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240603060606 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `UserPointValue` SET Value = 100 WHERE Value >= 100.001');
    }

    public function down(Schema $schema): void
    {
    }
}
