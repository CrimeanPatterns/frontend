<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190930073519 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("create table CreditCardEmail(
            CreditCardEmailID int not null auto_increment,
            Template varchar(80) not null comment 'Название шаблона в папке bundles/AwardWallet/MainBundle/FrameworkExtension/Mailer/Template/Offer/Chase',
            Enabled tinyint not null default 0 comment 'Будет ли участвовать в рассылке',
            unique key akTemplate(Template),
            primary key (CreditCardEmailID)
        ) engine=InnoDB comment 'Включенные шаблоны для SendChaseEmailsCommand'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("drop table CreditCardEmail");
    }
}
