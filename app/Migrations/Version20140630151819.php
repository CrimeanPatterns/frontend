<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140630151819 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE UserAgent ADD Birthday DATETIME  NULL COMMENT 'Дата рождения (для членов семьи и приконекченных). Используется в букинге'  AFTER LastName;
		");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
			ALTER TABLE UserAgent DROP Birthday;
		");
    }
}
