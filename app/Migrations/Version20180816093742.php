<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180816093742 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Account add SentToUpdateDate datetime comment 'Когда аккаунт был отправлен на проверку'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
