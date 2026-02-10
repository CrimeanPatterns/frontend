<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20140203102442 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("alter table Provider
        	add column AutoLoginIE tinyint comment 'Работает ли Автологин в IE',
		 	add column AutoLoginSafari tinyint comment 'Работает ли Автологин в Safari',
		 	add column AutoLoginChrome tinyint comment 'Работает ли Автологин в хроме',
		 	add column AutoLoginFirefox tinyint comment 'Работает ли Автологин в Файрфоксе'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("alter table Provider
			drop column AutoLoginIE,
			drop column AutoLoginSafari,
			drop column AutoLoginChrome,
			drop column AutoLoginFirefox");
    }
}
