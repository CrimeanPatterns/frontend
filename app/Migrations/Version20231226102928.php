<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231226102928 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table EmailFromAddress add column Verified tinyint not null default 0');
        $this->addSql('update EmailFromAddress set Verified = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table EmailFromAddress drop column Verified');
    }
}
