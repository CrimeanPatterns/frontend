<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211216101010 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE CreditCard SET QsCreditCardID=NULL WHERE QsCreditCardID = 269;');
    }

    public function down(Schema $schema): void
    {
    }
}
