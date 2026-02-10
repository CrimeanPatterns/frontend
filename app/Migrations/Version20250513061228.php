<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513061228 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Provider
                CHANGE `State` `State` TINYINT NOT NULL DEFAULT 0 COMMENT 'если не надо чтобы народ уже начал добавлять программы то пока код полностью не готов надо чтобы порграмма была disabled. Если уже народ надобавлял програм и ее поменяли на disabled то программа полностью исчезнет с сайта. Т.е. ее нельзя будет добавить и уже добавленные программы (например с ошибками) тоже исчезнут из профилей людей. Потом поменяв на enabled все появится назад.\n*Collecting requests* - копим запросы на добавление\n*Collecting accounts* - собираем аккаунты, кренделя приходят на почту, при проверке выдается UE\n*Beta users only* - ?\n*In development* - ?\n*Enabled* - включена, работает\n*Fixing* - находится в починке\n*Checking off* - нет проверки аккаунтов в бэкграунде\n*Checking only through extension* - нет никакой проверки, кроме как через extension\n*Disabled* - выключена\n*Test* - ?\n*WSDL Only* - ?\n*Hidden provider(e-mail parsing)* - ?'
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
