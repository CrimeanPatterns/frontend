<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230524114603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Usr`
                ADD COLUMN `TripitOauthToken` JSON NULL COMMENT 'Токены для доступа к Tripit API'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(/** @lang MySQL */"
            ALTER TABLE `Usr`
                DROP COLUMN `TripitOauthToken`");
    }
}
