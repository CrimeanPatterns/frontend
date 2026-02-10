<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201207170204 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table `EmailTemplate` add column `Exclusions` json comment 'Список исключений для выборки: [\'email_10\', \'data_us_users\']'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `EmailTemplate` drop column `Exclusions`');
    }
}
