<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140618085404 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE AbRequest ADD SendMailUser TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1' COMMENT 'Отправлять ли письма, связанные с букингом автору букинг-запроса'  AFTER ByBooker;
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE AbRequest DROP SendMailUser;
		");
    }
}
