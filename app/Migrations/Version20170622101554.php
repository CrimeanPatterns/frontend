<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170622101554 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `CardImage` 
                add column `UUID` varchar(64) default null comment 'идентификатор для асинхронной загрузки нескольких изображений',
                add key `CardImage_UserID_UUID`(`UserID`, `UUID`),
                add column `DetectedProviderID` int(11) default null comment 'провайдер с детекта изображения'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            alter table `CardImage`
                drop key `CardImage_UserID_UUID`,
                drop column `UUID`,           
                drop column `DetectedProviderID`
        ');
    }
}
