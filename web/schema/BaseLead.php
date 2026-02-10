<?php

class TBaseLeadSchema extends TBaseSchema
{
    public function __construct()
    {
        global $Config;
        parent::TBaseSchema();
        $this->TableName = "SiteAd";
        $this->KeyField = $this->TableName . "ID";
        $this->Description = ["Sales and Marketing", "Site Leads"];
        $this->Fields = [
            $this->KeyField => [
                "Caption" => "id",
                "Type" => "integer",
                "Size" => 250,
                "filterWidth" => 20,
                "Required" => true,
                "Sort" => "SiteADID DESC",
            ],
            "Description" => [
                "Caption" => "Description",
                "Type" => "string",
                "InputAttributes" => "style=\"width: 300px;\"",
                "Size" => 250,
                "Required" => true,
            ],
            "StartDate" => [
                "Caption" => "Start Date",
                "Type" => "date",
                "InputType" => "date",
                "InputAttributes" => "style=\"width: 100px;\"",
                "Required" => true,
            ],
            "LastClick" => [
                "Caption" => "Last Click",
                "Type" => "date",
                "InputType" => "date",
                "InputAttributes" => "disabled style=\"width: 100px;\"",
            ],
            "LastRegister" => [
                "Caption" => "Last Register",
                "Type" => "date",
                "InputType" => "date",
                "InputAttributes" => "disabled style=\"width: 100px;\"",
            ],
            "LastAppInstall" => [
                "Caption" => "Last App Install",
                "Type" => "date",
                "InputType" => "date",
                "InputAttributes" => "disabled style=\"width: 100px;\"",
            ],
            "Clicks" => [
                "Caption" => "Clicks",
                "Type" => "integer",
                "InputAttributes" => "disabled style=\"width: 100px;\"",
                "Size" => 250,
                "filterWidth" => 30,
                "Value" => 0,
            ],
            "Registers" => [
                "Caption" => "Registers",
                "Type" => "integer",
                "InputAttributes" => "disabled style=\"width: 100px;\"",
                "Size" => 250,
                "filterWidth" => 20,
                "Value" => 0,
            ],
            "AppInstalls" => [
                "Caption" => "App Installs",
                "Type" => "integer",
                "InputAttributes" => "disabled style=\"width: 100px;\"",
                "Size" => 250,
                "filterWidth" => 20,
                "Value" => 0,
            ],
            "Effectiveness" => [
                "Caption" => "Effectiveness (Registers/Clicks)",
                "Type" => "customCode",
                "Database" => false,
                "InputAttributes" => "style=\"width: 300px;\"",
                "Size" => 250,
                "Value" => "if(\$arFields[\"Clicks\"] != 0){\$mainWidth = number_format( \$arFields[\"Registers\"] / \$arFields[\"Clicks\"] * 100, 0, \".\", \",\" ); \$remWidth = 100 - (float) \$mainWidth; \$result = \"<table width=170 border=0 id='noBorder' cellspacing=0 cellpadding=0><tr>\"; if(\$mainWidth != 0){\$result .= \"<td bgcolor='#AE0001' width='\" . \$mainWidth . \"'>\" . PIXEL . \"</td>\";}  if(\$remWidth != 0){\$result .= \"<td bgcolor='#FFD090' width='\" . \$remWidth . \"'>\" . PIXEL . \"</td>\";} \$result .= \"<td nowrap style='font-size: 10px;'>&nbsp;(\" . number_format( \$arFields[\"Registers\"] / \$arFields[\"Clicks\"] * 100, 2, \".\", \",\" ) . \"%)</td></tr></table>\"; return \$result;}",
            ],
            "BookerID" => [
                "Caption" => "Booker",
                "Type" => "integer",
                "Required" => false,
            ],
            "Link Sample" => [
                "Caption" => "Link Sample",
                "Type" => "customCode",
                "InputAttributes" => "style=\"width: 300px;\"",
                "Size" => 250,
                "Value" => "return \"<a target='_blank' href='/register?ref=\" . \$arFields[\"SiteAdID\"] .\"&code='>sample</a>\";",
            ],
        ];
    }

    public function GetListFields()
    {
        $arFields = $this->Fields;

        //		unset($arFields["Answer"]);
        return $arFields;
    }

    public function TuneList(&$list)
    {
        /* @var $list TBaseList */
        parent::TuneList($list);
        $list->SQL = "SELECT * FROM " . $this->TableName;
        $list->MultiEdit = false;
        $list->KeyField = $this->KeyField;
    }

    public function TuneForm(TBaseForm $form)
    {
        $form->KeyField = $this->KeyField;
    }

    public function GetFormFields()
    {
        $arFields = $this->Fields;
        unset($arFields[$this->KeyField]);
        unset($arFields["Effectiveness"]);
        unset($arFields["Link Sample"]);

        $manager = new TTableLinksFieldManager();
        $manager->TableName = "SiteAdUser";
        $manager->Fields = [
            "UserID" => [
                "Caption" => "User",
                "Type" => "integer",
                "Options" => SQLToArray("select ua.AgentID, concat(u.FirstName, ' ', u.LastName) as Name 
                    from UserAgent ua
                    join Usr u on ua.AgentID = u.UserID
                    where ua.AccessLevel = " . ACCESS_BOOKING_VIEW_ONLY . " and ua.IsApproved = 1", "AgentID", "Name"),
            ],
        ];

        $arFields["Referrals"] = [
            "Caption" => "Booking View-only users",
            "Manager" => $manager,
            "Required" => false,
        ];

        return $arFields;
    }
}
