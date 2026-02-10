<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180815130825 extends AbstractMigration
{
    private const BATCH_SIZE = 10000;

    public function up(Schema $schema): void
    {
        $maxAccountIdInDb = (int) $this->connection->executeQuery('select max(AccountID) from Account')->fetchColumn(0);
        $affectedRows = 0;

        for ($maxAccId = 0; $maxAccId <= $maxAccountIdInDb; $maxAccId += self::BATCH_SIZE) {
            $affectedRows += $this->update($maxAccId - self::BATCH_SIZE, $maxAccId);

            if (
                ($affectedRows > 0)
                && ($affectedRows % (self::BATCH_SIZE * 5) === 0)
            ) {
                $this->write("Updated {$affectedRows} row(s)");
            }
        }

        $this->write("Updated {$affectedRows} row(s)");
        $this->write("Done!");
    }

    public function down(Schema $schema): void
    {
    }

    protected function update(int $minAccountId, int $maxAccountId)
    {
        $loyaltyKinds = [
            PROVIDER_KIND_SHOPPING,
            PROVIDER_KIND_DINING,
        ];

        $stmt = $this->connection->executeQuery(/** @lang MySQL */ "
                update Account a
                join (
                    select
                        accounts.ContainerID,
                        accounts.ContainerType,
                        accounts.LoyaltyName,
                        accounts.LoyaltyURL,
                        accounts.ProviderID,
                        accounts.UserID,
                        
                        apBarCode.Val as ParsedBarCode,
                        apBarCodeType.Val as ParsedBarCodeType,    
                    
                        coalesce(
                            if (apNumber.Val   = '', null, apNumber.Val),
                            if (accounts.Login = '', null, accounts.Login)
                        ) as BarCode,
                        accounts.BarCodeType,
                        
                        clp.Value as ScannedBarCode,
                        
                        accounts.LocationPropertyCode,
                        accounts.LocationPropertyValue,
                        
                        accounts.Popularity,
                        accounts.ChangeCount     
                    from (
                            select distinct 
                                a.AccountID as ContainerID,
                                'Account' as `ContainerType`,
                                coalesce(p.ShortName, a.ProgramName) as `LoyaltyName`,
                                coalesce(p.LoginURL, a.LoginURL) as `LoyaltyURL`,
                                p.ProviderID as ProviderID,
                                a.UserID,
                                
                                a.Login,
                                p.BarCode as BarCodeType,
                                
                                null as LocationPropertyCode,
                                null as LocationPropertyValue,
                                
                                p.Accounts as Popularity,
                                a.ChangeCount
                            from `Account` a
                            left join Provider p on a.ProviderID = p.ProviderID
                            left join AccountProperty ap on
                                a.AccountID = ap.AccountID and
                                ap.SubAccountID is null and
                                ap.ProviderPropertyID = " . 4094 . "
                            where
                                (a.AccountID between :minId and :maxId) and
                                greatest(a.CreationDate, a.UpdateDate) < DATE_ADD(NOW(), interval -2 month) and
                                (
                                    (
                                        a.Kind in (:loyaltyKinds) or
                                        p.Kind in (:loyaltyKinds)
                                    ) or
                                    p.State = " . PROVIDER_RETAIL . "
                                ) and
                                a.UserAgentID is null and
                                a.LastStoreLocationUpdateDate is null
                    ) accounts
                    left join `ProviderProperty` ppNumber on
                        accounts.ProviderID = ppNumber.ProviderID and
                        ppNumber.Kind = " . PROPERTY_KIND_NUMBER . "
                        
                    left join `AccountProperty` apNumber on
                        apNumber.AccountID = accounts.ContainerID and
                        apNumber.SubAccountID is null and
                        apNumber.ProviderPropertyID = ppNumber.ProviderPropertyID
                         
                    left join `AccountProperty` apBarCode on
                        apBarCode.AccountID = accounts.ContainerID and
                        apBarCode.SubAccountID is null and
                        apBarCode.ProviderPropertyID = " . 4094 . "
                        
                    left join `AccountProperty` apBarCodeType on
                        apBarCodeType.AccountID = accounts.ContainerID and
                        apBarCodeType.SubAccountID is null and
                        apBarCodeType.ProviderPropertyID = " . 4095 . "    
                        
                    left join CustomLoyaltyProperty clp on
                        accounts.ContainerID = clp.AccountID and
                        clp.Name = 'BarCodeData'  
                ) stat on
                    stat.ContainerID = a.AccountID
                set a.LastStoreLocationUpdateDate = greatest(a.UpdateDate, a.CreationDate)
                where
                    NOT(
                        (trim(stat.ScannedBarCode) = '' OR stat.ScannedBarCode is null) and
                        not(
                            (trim(stat.ParsedBarCodeType) <> '' and stat.ParsedBarCodeType is not null) and
                            (trim(stat.ParsedBarCode) <> ''     and stat.ParsedBarCode is not null) 
                        ) and 
                        not(
                            (trim(stat.BarCodeType) <> '' and stat.BarCodeType is not null) and
                            (trim(stat.BarCode) <> '' and stat.BarCode is not null)
                        )
                    )",

            [
                ':minId' => $minAccountId,
                ':maxId' => $maxAccountId,
                ':loyaltyKinds' => $loyaltyKinds,
            ],
            [
                ':minId' => \PDO::PARAM_INT,
                ':maxId' => \PDO::PARAM_INT,
                ':loyaltyKinds' => Connection::PARAM_INT_ARRAY,
            ]
        );

        return $stmt->rowCount();
    }
}
