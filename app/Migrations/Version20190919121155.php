<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190919121155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` 
                CHANGE `HistoryPatterns` `HistoryPatterns` MEDIUMTEXT NULL DEFAULT NULL COMMENT 'Паттерны для детекта существования карты по записями истории аккаунта кобрендового провайдера',
                CHANGE `CobrandProviderID` `CobrandProviderID` INT(11) NULL DEFAULT NULL COMMENT 'Кобренд провайдер, мили которого зарабатывает карта';
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
