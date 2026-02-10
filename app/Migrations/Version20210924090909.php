<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210924090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE `UserSubAccountStorage`');
        $this->addSql('DROP TABLE `UserCreditCardStorage`');
    }

    public function down(Schema $schema): void
    {
    }
}
