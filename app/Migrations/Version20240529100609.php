<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240529100609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table `EmailTemplate` add column `ExcludedCreditCards` JSON default null default ('[]')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `EmailTemplate` drop column `ExcludedCreditCards`');
    }
}
