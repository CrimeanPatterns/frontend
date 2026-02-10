<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190806054929 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $accountIds = [];

        foreach (
            $this->connection->executeQuery('select * from Account where Kind = 11 and ProviderID is null') as $row
        ) {
            $accountIds[] = $row['AccountID'];
        }

        if (!empty($accountIds)) {
            $accountIds = implode(",", $accountIds);

            $this->addSql("
                UPDATE Account SET Kind = 5 WHERE AccountID IN ({$accountIds})
            ");
            $this->write("Accounts updated: {$accountIds} \n");
        }

        $couponIds = [];

        foreach (
            $this->connection->executeQuery('select * from ProviderCoupon where Kind = 11 and TypeId not in (8, 9)') as $row
        ) {
            $couponIds[] = $row['ProviderCouponID'];
        }

        if (!empty($couponIds)) {
            $couponIds = implode(",", $couponIds);

            $this->addSql("
                UPDATE ProviderCoupon SET Kind = 5 WHERE ProviderCouponID IN ({$couponIds})
            ");

            $this->write("Coupons updated: {$couponIds} \n");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
