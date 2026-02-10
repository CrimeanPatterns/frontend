<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171026101134 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage`
                drop key `CardImage_UserID_UUID`,
                 
                change column `UUID` `ClientUUID`
                    varchar(64)
                    default null
                    comment 'клиентский идентификатор для асинхронной загрузки нескольких изображений',
                    
                add key `CardImage_UserID_ClientUUID`(`UserID`, `ClientUUID`),
                    
                add column `UUID`
                    char(36)
                    not null default ''
                    comment 'уникальный идентификатор для доступа к картинке из отчетов по карточкам'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage`
                drop column `UUID`,
                
                drop key `CardImage_UserID_ClientUUID`,
                
                change column `ClientUUID` `UUID`
                    varchar(64)
                    default null
                    comment 'идентификатор для асинхронной загрузки нескольких изображений',
                    
                add key `CardImage_UserID_UUID`(`UserID`, `UUID`)
        ");
    }
}
