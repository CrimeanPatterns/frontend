<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241205101159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("alter table Usr modify `EmailNewBlogPosts` tinyint unsigned NOT NULL DEFAULT " . NotificationModel::BLOGPOST_NEW_NOTIFICATION_WEEK . " COMMENT 'Отправлять ли письма о новых сообщениях в блоге'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
