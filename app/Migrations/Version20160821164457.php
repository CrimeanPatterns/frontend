<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160821164457 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` ADD `UpdateDate` DATETIME NOT NULL DEFAULT '2016-01-01 00:00:00' COMMENT 'Дата изменения информации о букере(через форму). По дефолту дата создания букера(если присутствует, иначе - 2016-01-01 00:00:00)'");

        $this->addSql('
            update AbBookerInfo abi
            join Usr u on abi.UserID = u.UserID
            set abi.UpdateDate = u.CreationDateTime
            where u.CreationDateTime is not null
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `AbBookerInfo` DROP `UpdateDate`");
    }
}
