<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211208080808 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Hotel` ADD `GooglePlaceDetails` JSON NULL DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
