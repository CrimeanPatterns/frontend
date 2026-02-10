<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230802120641 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("alter table AirCode add column AddressLine varchar(250) comment 'Строка адреса'");

    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table AirCode drop column AddressLine');

    }
}
