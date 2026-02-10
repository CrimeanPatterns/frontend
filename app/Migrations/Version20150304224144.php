<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150304224144 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
        CREATE TABLE ext_translations (
        	id INT AUTO_INCREMENT NOT NULL,
        	locale VARCHAR(8) NOT NULL,
        	object_class VARCHAR(255) NOT NULL,
        	field VARCHAR(32) NOT NULL,
        	foreign_key VARCHAR(64) NOT NULL,
        	content LONGTEXT DEFAULT NULL,
        	INDEX translations_lookup_idx (locale, object_class, foreign_key),
        	UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key),
        	PRIMARY KEY(id)
		) ENGINE = InnoDB COMMENT 'Переводы для данных из базы, например Faq.Question, Faq.Answer'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE ext_translations");
    }
}
