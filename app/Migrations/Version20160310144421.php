<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160310144421 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE Account SET GoalAutoSet = 0 WHERE GoalAutoSet = 1 and Goal > 0');
        $this->addSql('UPDATE Account SET GoalAutoSet = 1 WHERE GoalAutoSet = 0 and Goal is null');
    }

    public function down(Schema $schema): void
    {
    }
}
