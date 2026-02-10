<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ShoppingCategoryGroupList extends \TBaseList
{
    private array $categoriesByGroup;
    private array $bonusesByGroup;

    public function __construct(
        string $table,
        array $fields,
        Connection $connection
    ) {
        parent::__construct($table, $fields);

        $this->SQL = "select * from ShoppingCategoryGroup";

        $this->categoriesByGroup = it(
            $connection->executeQuery("
                select ShoppingCategoryGroupID, ShoppingCategoryID, Name 
                from ShoppingCategory where ShoppingCategoryGroupID is not null
                order by ShoppingCategoryGroupID, Name")->fetchAllAssociative()
        )
            ->reindexByColumn("ShoppingCategoryGroupID")
            ->collapseByKey()
            ->toArrayWithKeys()
        ;

        $this->bonusesByGroup = it(
            $connection->executeQuery("
                select 
                    cc.Name,
                    ccscg.CreditCardID,
                    ccscg.Multiplier,
                    ccscg.ShoppingCategoryGroupID
                from
                    CreditCardShoppingCategoryGroup ccscg
                    join CreditCard cc on ccscg.CreditCardID = cc.CreditCardID
                where
                    ccscg.EndDate is null or ccscg.EndDate > now()
                order by
                    ccscg.Multiplier desc")->fetchAllAssociative()
        )
            ->reindexByColumn("ShoppingCategoryGroupID")
            ->collapseByKey()
            ->toArrayWithKeys()
        ;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output === "html") {
            $this->Query->Fields['Categories'] = $this->addUl(it($this->categoriesByGroup[$this->OriginalFields["ShoppingCategoryGroupID"]] ?? [])
                ->map(fn (array $cat) => "<li><a href=\"edit.php?Schema=ShoppingCategory&ID={$cat['ShoppingCategoryID']}\" target=\"blank\">{$cat['Name']}</a></li>")
                ->joinToString("\n"))
            ;
            $this->Query->Fields['BonusEarns'] = $this->addUl(it($this->bonusesByGroup[$this->OriginalFields["ShoppingCategoryGroupID"]] ?? [])
                ->map(fn (array $cat) => "<li><a href=\"edit.php?Schema=CreditCard&ID={$cat['CreditCardID']}\" target=\"blank\">{$cat['Name']} - {$cat['Multiplier']}</a></li>")
                ->joinToString("\n"))
            ;

            if ($this->Query->Fields['ClickURL'] !== null) {
                $this->Query->Fields['Name'] = "<a href=\"{$this->Query->Fields['ClickURL']}\" target=\"_blank\">{$this->Query->Fields['Name']}</a>";
            }
        }
    }

    public function GetEditLinks()
    {
        $result = parent::GetEditLinks();

        $row = $this->OriginalFields;

        $result .= " <a target='_blank' href='list.php?Schema=CreditCardShoppingCategoryGroup&ShoppingCategoryGroupID={$row['ShoppingCategoryGroupID']}'>Credit Cards</a>";

        return $result;
    }

    private function addUl(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        return "<ul>" . $html . "</ul>";
    }
}
