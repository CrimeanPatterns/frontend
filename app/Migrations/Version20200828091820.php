<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200828091820 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            alter table `CreditCard` 
            add `CobrandSubAccPatterns` mediumtext COMMENT \'Паттерны для детекта существования карты по субаккаунтам кобрендового провайдера\' after `HistoryPatterns`;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` DROP `CobrandSubAccPatterns`;
        ");
    }
}
