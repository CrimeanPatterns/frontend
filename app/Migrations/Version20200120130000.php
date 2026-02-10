<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200120130000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE UserCreditCard SET IsClosed = 0 WHERE DetectedViaQS = 1');
    }

    public function down(Schema $schema): void
    {
    }
}
