<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20210409100000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `Provider` ADD `BlogTagsID` VARCHAR(255) NULL DEFAULT NULL COMMENT 'TagID для ссылки на блог на странице рейтингов'");
        $this->addSql("ALTER TABLE `Provider` ADD `BlogPostID` VARCHAR(255) NULL DEFAULT NULL COMMENT 'PostID блогпостов для вывода на странице рейтинга'");
    }

    public function down(Schema $schema): void
    {
    }
}
