<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180906021614 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table SubAccount 
            modify Balance decimal(18,2) COMMENT 'Баланс подаккаунта',
            modify LastBalance decimal(18,2) COMMENT 'Предыдущее значение баланса'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
