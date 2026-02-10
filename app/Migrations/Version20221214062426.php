<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221214062426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            delete RetailProviderMerchant
            from RetailProviderMerchant
            join (
                select 
                    stat.ProviderID,
                    stat.MerchantID
                from (
                    select 
                        rpm.ProviderID,
                        first_value(m.MerchantID) over (partition by rpm.ProviderID order by m.Transactions DESC) as MerchantID
                    from RetailProviderMerchant rpm
                    join Merchant m on rpm.MerchantID = m.MerchantID
                ) stat
                group by stat.ProviderID, stat.MerchantID
            ) statDelete on RetailProviderMerchant.ProviderID = statDelete.ProviderID
            where RetailProviderMerchant.MerchantID <> statDelete.MerchantID;
        ');
        $this->addSql('alter table `RetailProviderMerchant` ADD UNIQUE KEY `ProviderMerchant` (`ProviderID`, `MerchantID`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table `RetailProviderMerchant` drop key `ProviderMerchant`');
    }
}
