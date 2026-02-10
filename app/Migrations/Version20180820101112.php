<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180820101112 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `Provider` CHANGE `LoginCaption` `LoginCaption` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
            COMMENT 'что требуется ввести для логина. В случае когда надо дать подсказку можно ввести Login (Mileage Plus # or email or screen name) тогда то что в скобках покажется в виде подсказки под полем и не ипортит форму длинным названием поля.*Value*:Mileage Plus #';
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
