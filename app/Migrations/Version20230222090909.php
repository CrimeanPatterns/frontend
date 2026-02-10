<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230222090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard`
                ADD `IsVisibleInAll` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Попадание в раздел All Cards в фильтре best-cards в блоге',
                ADD `IsVisibleInBest` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Попадание в раздел Best Cards в фильтре best-cards в блоге';
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `CreditCard`
                DROP `IsVisibleInAll`,
                DROP `IsVisibleInBest`;
        ');
    }
}
