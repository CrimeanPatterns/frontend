<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161003112348 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('Usr');
        $table->addColumn('DiscountedUpgradeBefore', 'datetime', [
            'comment' => 'До какой даты пользователь получает возможность оформить Aw Plus со скидкой',
            'after' => "PlusExpirationDate",
        ]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('Usr');
        $table->dropColumn('DiscountedUpgradeBefore');
    }
}
