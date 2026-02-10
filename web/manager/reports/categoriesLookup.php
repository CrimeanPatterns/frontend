<?php
require("../start.php");
require_once "$sPath/lib/classes/TBaseList.php";

class CategoriesLookupList extends TBaseList
{

    function FormatFields($output = "html")
    {
        parent::FormatFields($output);
        $router = getSymfonyContainer()->get('router');
        $ccLink = $router->generate('credit_card_edit', ['id' => $this->Query->Fields['CCID']]);
        $this->Query->Fields['CreditCardID'] = "<a href='{$ccLink}'>" . $this->Query->Fields['CreditCardID'] . "</a>";
        $transactionsLink = $router->generate("aw_manager_transaction_list", [
            "ShoppingCategoryID" => $this->Query->Fields['SCID'],
            "CreditCardID" => $this->Query->Fields['CCID'],
            "Multiplier" => $this->Query->Fields['Multiplier'],
        ]);
        $this->Query->Fields['Transactions'] = "<a href='{$transactionsLink}'>" . $this->Query->Fields['Transactions'] . "</a>";
    }

}


$list = new CategoriesLookupList('ShoppingCategoryMultiplier', [
    "ShoppingCategoryID" => array(
        "Type" => "integer",
        "Caption" => "Shopping Category",
        "FilterField" => "a.ShoppingCategoryID",
        "Options" => SQLToArray(
            "select 0 as ShoppingCategoryID, 'No Category' as Name
                   union all
                   (select ShoppingCategoryID, Name from ShoppingCategory order by Name)",
            "ShoppingCategoryID",
            "Name"
        ),
    ),
    "SCID" => array(
        "Type" => "integer",
        "Caption" => "Category ID",
        "FilterField" => "a.ShoppingCategoryID",
    ),
    "ShoppingCategoryGroupID" => array(
        "Type" => "integer",
        "Caption" => "Category Group",
        "FilterField" => "sc.ShoppingCategoryGroupID",
        "Options" => SQLToArray(
            "select 0 as ShoppingCategoryGroupID, 'No Group' as Name
                   union all
                   (select ShoppingCategoryGroupID, Name from ShoppingCategoryGroup order by Name)",
            "ShoppingCategoryGroupID",
            "Name"
        ),
    ),
    "SCGID" => array(
        "Type" => "integer",
        "Caption" => "Group ID",
        "FilterField" => "a.ShoppingCategoryGroupID",
    ),
    "CreditCardID" => array(
        "Type" => "integer",
        "Caption" => "Credit Card",
        "FilterField" => "a.CreditCardID",
        "Options" => SQLToArray("select CreditCardID, Name 
			    from CreditCard order by Name", "CreditCardID", "Name"),
    ),
    "Multiplier" => [
        "Type" => "float",
        "Sort" => "Multiplier DESC"
    ],
    "Transactions" => [
        "Type" => "integer",
        "Sort" => "Transactions DESC"
    ],
], 'Transactions');

$list->SQL = <<<SQL
    SELECT a.*, a.ShoppingCategoryID as SCID, sc.ShoppingCategoryGroupID, sc.ShoppingCategoryGroupID as SCGID, a.CreditCardID as CCID
    FROM ShoppingCategoryMultiplier a
    JOIN ShoppingCategory sc on a.ShoppingCategoryID = sc.ShoppingCategoryID
    LEFT JOIN ShoppingCategoryGroup scg ON scg.ShoppingCategoryGroupID = sc.ShoppingCategoryGroupID
SQL;

$list->ReadOnly = true;
$list->ShowFilters = true;
$list->ShowExport = true;
$list->PageSize = 100;

if (isset($_GET['exportReport'])) {

    $exportListFields = [
        "ID" => [
            "Type" => "string",
            "Caption" => "Credit Card",
        ],
        "ShoppingCategory" => [
            "Type" => "string",
            "Caption" => "Shopping Category",
        ],
        "SCID" => [
            "Type" => "integer",
            "Caption" => "Category ID",
        ],
        "CategoryGroup" => [
            "Type" => "string",
            "Caption" => "Category Group",
        ],
        "SCGID" => [
            "Type" => "integer",
            "Caption" => "Group ID",
        ],
        "Provider" => [
            "Type" => "string",
            "Caption" => "Provider",
        ],
    ];

    switch ($_GET['exportReport']) {
        case '1':
            {
                $exportList = new TBaseList('', $exportListFields, "ID");
                $exportList->SQL = "
                select cc.Name as ID, sc.Name as ShoppingCategory, sc.ShoppingCategoryID as SCID, scg.Name as CategoryGroup, sc.ShoppingCategoryGroupID as SCGID, p.DisplayName as Provider
                from ShoppingCategoryMultiplier d 
                    join ShoppingCategory sc on d.ShoppingCategoryID = sc.ShoppingCategoryID 
                    join CreditCard cc on d.CreditCardID = cc.CreditCardID
                    join Provider p on cc.ProviderID = p.ProviderID 
                    left join ShoppingCategoryGroup scg on sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                group by ID, ShoppingCategory, SCID, CategoryGroup, SCGID, Provider 
            ";
                $exportList->ExportName = 'UniqueCategoryCard';
                break;
            }
        case '2':
            {
                $exportList = new TBaseList('', array_merge($exportListFields, [
                    "Multiplier" => [
                        "Type" => "float",
                        "Sort" => "Multiplier DESC"
                    ],
                    "Transactions" => [
                        "Type" => "integer",
                        "Sort" => "Transactions DESC"
                    ],
                ]), "ID");
                $exportList->SQL = "
                select 
                    cc.Name as ID, sc.Name as ShoppingCategory, sc.ShoppingCategoryID as SCID, 
                    scg.Name as CategoryGroup, sc.ShoppingCategoryGroupID as SCGID,
                    p.DisplayName as Provider, Multiplier, Transactions
                from ShoppingCategoryMultiplier d 
                    join ShoppingCategory sc on d.ShoppingCategoryID = sc.ShoppingCategoryID 
                    join CreditCard cc on d.CreditCardID = cc.CreditCardID
                    join Provider p on cc.ProviderID = p.ProviderID 
                    left join ShoppingCategoryGroup scg on sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                where Multiplier between 1.9 and 10.1
                and Multiplier % 1 = 0
            ";
                $exportList->ExportName = 'IntegerMultiplierCombinations';
                break;
            }
    }
    $exportList->ExportCSV();
    return;
}

drawHeader("Categories Multiplier Lookup Report");
echo "<input class='button' type=button value=\"Export unique Category - Card\" onclick=\"location.href = '?exportReport=1'\"> ";
echo "<input class='button' type=button value=\"Export integer multiplier combinations\" onclick=\"location.href = '?exportReport=2'\"> ";

$list->Draw();
drawFooter();
