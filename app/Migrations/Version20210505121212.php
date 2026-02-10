<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20210505121212 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM UserCreditCard WHERE CreditCardID=163 AND IsClosed = 1 AND DetectedViaBank=1');
    }

    public function down(Schema $schema): void
    {
    }
}
