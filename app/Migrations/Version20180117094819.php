<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180117094819 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            alter table `Location` 
                add column `Generated` int(1) not null default 0 comment '0 - добавлена пользователм, 1 - локация была доавлена сервером автоматически'      
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `Location` drop column `Generated`');
    }
}
