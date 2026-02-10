<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200819120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `EmailTemplate`
                ADD `ListBlogPostID` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Список блогпостов прикрепляемый к телу письма',
                ADD `CID` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Переменная для URL ссылок со списка блогпостов',
                ADD `MID` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Переменная для URL ссылок со списка блогпостов';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `EmailTemplate`
                DROP `ListBlogPostID`,
                DROP `CID`,
                DROP `MID`;
        ");
    }
}
