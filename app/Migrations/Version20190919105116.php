<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190919105116 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table EmailLog
            add Template varchar(80) comment 'шаблон по которому был отправлен email',
            add Code varchar(80) comment 'уникальный код емэйла, может быть несколько емэйлов одинакового смысла, но разных по дизайну, верстке, a/b тестирование, с помощью этого кода предотвращаем посылку одного и того же емэйла в разных дизайнах',
            add index idx_Code(UserID, Code),
            modify MessageKind int not null comment 'Константы EmailLog::MESSAGE_KIND_ или EmailTemplate.EmailTemplateID. EmailTemplateID начинается со 151'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
