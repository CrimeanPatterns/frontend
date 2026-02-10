<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220130080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Usr`
                ADD `RegistrationPlatform` TINYINT NULL DEFAULT NULL COMMENT 'С какого устройства была регистрация. Usr::REGISTRATION_PLATFORM',
                ADD `RegistrationMethod` TINYINT NULL DEFAULT NULL COMMENT 'Как регистрировались. Через форму, oauth. Usr::REGISTRATION_METHOD' 
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
        ALTER TABLE `Usr`
            DROP `RegistrationPlatform`,
            DROP `RegistrationMethod`
        ');
    }
}
