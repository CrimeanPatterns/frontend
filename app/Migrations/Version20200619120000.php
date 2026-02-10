<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200619120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE Provider SET EnableDate = '2020-04-04 12:04:04' WHERE State = " . PROVIDER_ENABLED . " AND EnableDate IS NULL");
    }

    public function down(Schema $schema): void
    {
    }
}
