<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220427121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `CreditCard` ADD `IsApiReady` TINYINT(1) NOT NULL DEFAULT '0'
                COMMENT 'Флаг готовности карты к выдаче в json API credit-cards', ALGORITHM = INSTANT
        ");
        $this->addSql('UPDATE CreditCard SET IsApiReady=1 WHERE CreditCardID IN(
        20,23,26,48,96,102,97,99,101,19,24,25,21,28,115,38,116,100,40,131,95,98,120,127,114,69,54,63,178,188,67,92,
        72,91,189,73,190,136,143,191,133,141,142,144,145,146,147,148,149,175,192,193,194,196,195,167,197,43,173,111,
        198,168,112,216,177,15,17,18,36,106,119,126,152,153,180,1,3,5,6,49,132,130,51,105,52,107,108,109,104,118,117,
        124,125,161,50,163,128,155,160,174,139,200,204,202,203,29,30,31,134,138,140,201,39,179,205,206,207,208,166,210,
        211,212,164,184,181,182,183,213,214,215
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `CreditCard` DROP `IsApiReady`');
    }
}
