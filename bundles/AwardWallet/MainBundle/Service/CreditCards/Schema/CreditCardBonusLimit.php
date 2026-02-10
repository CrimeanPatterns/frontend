<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use Doctrine\DBAL\Connection;

class CreditCardBonusLimit extends \TBaseSchema
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;

        $this->Fields["OnAllCategories"]["Type"] = "boolean";

        $this->Fields["Period"]["Options"] = [
            "" => "",
            "Y" => "Yearly",
            "Q" => "Quarterly",
            "M" => "Monthly",
            "1" => "1 Month",
            "2" => "2 Months",
            "3" => "3 Months",
            "4" => "4 Months",
            "5" => "5 Months",
            "6" => "6 Months",
            "7" => "7 Months",
            "8" => "8 Months",
            "9" => "9 Months",
            "X" => "10 Months",
            "W" => "11 Months",
            "Z" => "1 Year",
        ];

        $this->Fields["PeriodBeginsOn"]["Options"] = [
            "" => "",
            "A" => "Account anniversary date",
            "1" => "January 1",
        ];

        $this->Fields["SignupBonusCurrency"]["Options"] = [
            "" => "",
            "M" => "Miles",
            "D" => "USD",
        ];

        $this->Fields["CCOpenedBy"]["Caption"] = "CC Opened By";
        $this->Fields["MustRegister"]["Type"] = "boolean";
        $this->Fields["Targeted"]["Type"] = "boolean";
        $this->Fields["NewAccountsOnly"]["Type"] = "boolean";
        $this->Fields["SignupBonus"]["RequiredGroup"] = "bonus";
        $this->Fields["BonusMultiplier"]["RequiredGroup"] = "bonus";
        $this->Fields["IsFullStatementCredit"]["Caption"] = "100% Statement Credit";
        $this->Fields["SpendingLimit"]["Note"] = "(In USD)";
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        $options = $this->connection->fetchAllKeyValue("select ShoppingCategoryGroupID, Name from ShoppingCategoryGroup order by Name");

        $groupManager = new \TTableLinksFieldManager();
        $groupManager->TableName = "CreditCardBonusLimitGroup";
        $groupManager->KeyField = "CreditCardBonusLimitID";
        $groupManager->Fields = [
            "ShoppingCategoryGroupID" => [
                "Type" => "integer",
                "Caption" => "Group",
                "Options" => $options,
                "Required" => true,
            ],
        ];
        $groupManager->CanEdit = true;
        $groupManager->UniqueFields = ["ShoppingCategoryGroupID"];

        ArrayInsert($result, "OnAllCategories", true, [
            "Categories" => [
                "Manager" => $groupManager,
            ],
        ]);

        return $result;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        \ArrayInsert(
            $result,
            "CreditCardID",
            true,
            [
                "Categories" => [
                    'Type' => 'string',
                    'Database' => false,
                ],
            ]
        );

        return $result;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->SQL = "select 
            *, 
            (
                select group_concat(gn.Name) from CreditCardBonusLimitGroup g
                join ShoppingCategoryGroup gn on g.ShoppingCategoryGroupID = gn.ShoppingCategoryGroupID
                where g.CreditCardBonusLimitID = CreditCardBonusLimit.CreditCardBonusLimitID
            ) as Categories 
        from 
             CreditCardBonusLimit 
        where 
            1 = 1 [Filters]";
    }
}
