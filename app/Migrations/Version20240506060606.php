<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240506060606 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE Account SET PointValue = ' . MileValueService::MAX_VALUE_INPUT_USER . ' WHERE PointValue > ' . MileValueService::MAX_VALUE_INPUT_USER);
    }

    public function down(Schema $schema): void
    {
    }
}
