<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131107160356 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        return;
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE Account MODIFY Balance FLOAT DEFAULT NULL COMMENT 'Баланс аккаунта (null, если нет)'");
        $this->addSql("ALTER TABLE Account MODIFY LastBalance FLOAT DEFAULT NULL COMMENT 'Предыдущее значение баланса'");

        $this->addSql("ALTER TABLE AccountBalance MODIFY Balance FLOAT NOT NULL COMMENT 'Новый баланс'");

        $this->addSql("ALTER TABLE AccountHistory MODIFY Miles float DEFAULT NULL COMMENT 'Количество начисленных / списанных баллов'");
        $this->addSql("ALTER TABLE AccountHistory MODIFY Info mediumtext COMMENT 'Сериализованные данные, содержающую другую полезную информацию о транзакции (Tier Points, Bonus и т.д.)'");

        $this->addSql("ALTER TABLE BonusConversion MODIFY Cost float DEFAULT NULL COMMENT 'Сколько заплатила AW, чтобы купить мили.'");

        $this->addSql("ALTER TABLE Flights MODIFY SavingsAmount float DEFAULT NULL COMMENT 'Сумма скидок'");
        $this->addSql("ALTER TABLE Flights MODIFY SavingsConfirmed float DEFAULT NULL COMMENT 'Подтвержденные скидки'");

        $this->addSql("ALTER TABLE Trip MODIFY SavingsAmount float DEFAULT NULL COMMENT 'Сумма скидок'");
        $this->addSql("ALTER TABLE Trip MODIFY SavingsConfirmed float DEFAULT NULL COMMENT 'Подтвержденные скидки'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
