<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230802121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Usr` ADD `IsBlogPostAds` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Показывать рекламу при просмотре поста в блоге',
                ALGORITHM=INSTANT  
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Usr` DROP `IsBlogPostAds`');
    }
}
