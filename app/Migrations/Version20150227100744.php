<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150227100744 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RewardsTransfer ADD Comment TEXT DEFAULT NULL COMMENT "Комментарий"');
        $this->addSql('ALTER TABLE RewardsTransfer ADD Tested TINYINT(4) NOT NULL DEFAULT 0 COMMENT "Проверен ли перевод бонусов"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE RewardsTransfer DROP Tested');
        $this->addSql('ALTER TABLE RewardsTransfer DROP Comment');
    }
}
