<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150817131902 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Usr` ADD `ItineraryAddDate` DATETIME  NULL  COMMENT 'Дата последнего добавления резерваций в профиль юзера. Необходима для определения времени отправки письма о новых резервациях'  AFTER `PlansUpdateDate`;
            ALTER TABLE `Usr` ADD `ItineraryUpdateDate` DATETIME  NULL  COMMENT 'Дата последнего изменения резервации в профиле юзера (изменение свойств резервации, входящих в белый список). Необходима для определения времени отправки письма об измененных резервациях'  AFTER `ItineraryAddDate`;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Usr` DROP `ItineraryAddDate`;
            ALTER TABLE `Usr` DROP `ItineraryUpdateDate`;
        ");
    }
}
