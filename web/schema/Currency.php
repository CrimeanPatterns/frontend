<?php

class TCurrencySchema extends TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();
        $this->TableName = "Currency";
        $this->Fields = [
            "Name" => [
                "Type" => "string",
                "Size" => 250,
                "Required" => true,
                "InputAttributes" => "style='width: 300px;'",
                "Caption" => "Name (Plural)<br>(ex: Rewards, Dollars etc.)",
            ],
            "Plural" => [
                "Type" => "string",
                "Size" => 250,
                "Required" => true,
                "InputAttributes" => "style='width: 300px;'",
                "Caption" => "Plural <br>(for translations, ex. 'Dollar|Dollars', 'mile|miles')",
            ],
            "Sign" => [
                "Type" => "string",
                "Size" => 20,
                "InputAttributes" => "style='width: 300px;'",
            ],
            "Code" => [
                "Type" => "string",
                "Size" => 20,
                "InputAttributes" => "style='width: 300px;'",
            ],
        ];
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();

        return $fields;
    }

    public function TuneForm(TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->Uniques = [
            [
                "Fields" => ["Name"],
                "ErrorMessage" => "This name already exists. Please choose another name.",
            ],
        ];
        $form->OnCheck = [$this, "checkForm", $form];
    }

    public function checkForm($form)
    {
        if (!empty($form->Fields['Sign']['Value'])) {
            $form->Fields['Sign']['Value'] = CleanXMLValue($form->Fields['Sign']['Value']);
        }
    }
}
