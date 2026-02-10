<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220425081243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table Merchant add IsCustomDisplayName tinyint default 0 comment 'Название мерчанта установлено через админку и не будет обновляться автоматически', ALGORITHM = INSTANT");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Merchant drop IsCustomDisplayName");
    }
}
