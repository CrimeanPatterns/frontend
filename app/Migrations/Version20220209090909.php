<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220209090909 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE `ProviderCoupon`
            SET Kind = " . PROVIDER_KIND_AIRLINE . "
            WHERE `ProgramName` LIKE 'American Airlines (AAdvantage)'
            AND Kind <> " . PROVIDER_KIND_AIRLINE
        );
    }

    public function down(Schema $schema): void
    {
    }
}
