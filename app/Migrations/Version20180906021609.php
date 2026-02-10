<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180906021609 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account 
            modify Balance decimal(18,2) COMMENT 'Баланс аккаунта',
            modify LastBalance decimal(18,2) COMMENT 'Предыдущее значение баланса',
            modify TotalBalance decimal(18,2) COMMENT 'Сумма балансов всех подаккаунтов и основного'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
