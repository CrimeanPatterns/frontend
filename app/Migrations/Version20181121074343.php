<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20181121074343 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS=0");
        $this->addSql("ALTER TABLE Trip ADD COLUMN IssuingAirlineConfirmationNumber varchar(50)");
        $this->addSql("SET FOREIGN_KEY_CHECKS=1");
    }

    public function down(Schema $schema): void
    {
    }
}
