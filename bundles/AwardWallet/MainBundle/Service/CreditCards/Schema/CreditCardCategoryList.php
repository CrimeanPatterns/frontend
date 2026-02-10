<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CreditCardCategoryList
{
    public function __construct(Connection $connection)
    {
        $this->categories = it($connection->executeQuery("
        select * from (
            select 
                'CreditCardShoppingCategoryGroup' as SchemaName,
                scg.Name,
                ccscg.CreditCardID,
                ccscg.CreditCardShoppingCategoryGroupID as ID,
                ccscg.Multiplier,
                case when scg.Name is null then 0 else 1 end HaveGroup,
                ccscg.ShoppingCategoryGroupID as GroupID
            from
                CreditCardShoppingCategoryGroup ccscg
                left join ShoppingCategoryGroup scg on ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
            where
                ccscg.EndDate is null or ccscg.EndDate > now()
            union select
                'CreditCardMerchantGroup' as SchemaName,
                mg.Name,
                ccmg.CreditCardID,
                ccmg.CreditCardMerchantGroupID as ID,
                ccmg.Multiplier,
                1 as HaveGroup,
                ccmg.MerchantGroupID as GroupID
            from
                CreditCardMerchantGroup ccmg
                join MerchantGroup mg on ccmg.MerchantGroupID = mg.MerchantGroupID
            where
                ccmg.EndDate is null or ccmg.EndDate > now()
        ) a
        order by 
            CreditCardID,
            HaveGroup,
            Multiplier DESC,     
            Name
        ")->fetchAllAssociative())
            ->reindex(fn ($row) => $row['CreditCardID'])
            ->collapseByKey()
            ->toArrayWithKeys()
        ;
    }

    public function getCategoriesList(int $creditCardId): string
    {
        $result = $this->categories[$creditCardId] ?? [];
        $result = it($result)
            ->map(fn ($row) => "<div><a target='_blank' "
                    . ($row['SchemaName'] === 'CreditCardMerchantGroup' ? 'style="color:red;" ' : "") .
                    "href='edit.php?Schema={$row['SchemaName']}&ID={$row['ID']}'>{$row['Multiplier']}x - {$row['Name']}</a></div>")
            ->joinToString("")
        ;

        return $result;
    }

    public function getCategories(int $creditCardId): array
    {
        return $this->categories[$creditCardId] ?? [];
    }
}
