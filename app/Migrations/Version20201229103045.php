<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201229103045 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge`
                ADD IsFullURL TINYINT COMMENT '1 - URL полный (указывает напрямую на страницу зала), 0 - частичный (может указывать на все или группу залов)' after URL
        ");
        $this->addSql('
            update `Lounge` l
            set l.IsFullURL = 1
            where l.SourceCode = \'priorityPass\' and l.URL is not null
        ');

        $this->addSql('
            update `Lounge` l
            set l.IsFullURL = 0
            where l.SourceCode = \'delta\' or l.SourceCode = \'skyTeam\' and l.URL is not null
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Lounge` 
                DROP IsFullURL
        ");
    }
}
