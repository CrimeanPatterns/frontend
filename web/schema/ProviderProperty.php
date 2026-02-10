<?php

use AwardWallet\MainBundle\Entity\Providerproperty;

require_once __DIR__ . "/Provider.php";

class TProviderPropertySchema extends TBaseSchema
{
    public function TProviderPropertySchema()
    {
        global $arPropertiesKinds;
        unset($arPropertiesKinds[PROPERTY_KIND_OTHER]);
        unset($arPropertiesKinds[PROPERTY_KIND_EXPIRATION]);
        $arPropertiesKinds = ["" => 'Basic'] + $arPropertiesKinds;
        parent::TBaseSchema();
        $this->TableName = "ProviderProperty";
        $this->Fields = [
            "ProviderPropertyID" => [
                "Caption" => "id",
                "Type" => "integer",
                "Required" => true,
                "InputAttributes" => " readonly",
                "filterWidth" => 30,
                "InplaceEdit" => false,
            ],
            "ProviderID" => [
                "Caption" => "Provider",
                "Type" => "integer",
                "Required" => false,
                "Options" => ["" => "All providers"] + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
                "InplaceEdit" => false,
            ],
            "Name" => [
                "Type" => "string",
                "Size" => 80,
                "Required" => true,
                "Sort" => "ProviderID,Name,SortIndex",
                "InplaceEdit" => false,
            ],
            "Code" => [
                "Type" => "string",
                "Size" => 40,
                "Required" => true,
                "Sort" => "ProviderID,Code",
                'RegExp' => '/^[A-Z]\w+$/ms',
                'Note' => "something like 'MyProperyName', words are starting with capital letter, without spaces",
                "InplaceEdit" => false,
            ],
            "Required" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => "1",
                "InplaceEdit" => false,
            ],
            "SortIndex" => [
                "Type" => "integer",
                "Required" => true,
                "Sort" => "ProviderID,SortIndex",
                "InplaceEdit" => false,
            ],
            "Kind" => [
                "Type" => "integer",
                "Required" => false,
                "InplaceEdit" => true,
                "Options" => $arPropertiesKinds,
            ],
            'Type' => [
                'Type' => 'integer',
                'Required' => false,
                'InplaceEdit' => true,
                'Options' => ['' => ''] + Providerproperty::TYPE_NAMES,
            ],
            "Visible" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => "1",
                "InplaceEdit" => true,
                "Options" => [
                    PROPERTY_INVISIBLE => 'Invisible',
                    PROPERTY_VISIBLE => 'Visible',
                    PROPERTY_VISIBLE_TO_PARTNERS => 'Visible to partners',
                    PROPERTY_INVISIBLE_TO_PARTNERS => 'Invisible to partners',
                ],
            ],
        ];
        $this->FilterFields = ["ProviderID"];
        $this->DefaultSort = "SortIndex";
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->InplaceEdit = true;

        if (ArrayVal($_SERVER, 'REMOTE_USER') == 'points') {
            $list->ShowImport = false;
            $list->AllowDeletes = false;
            $list->CanAdd = false;
            $list->MultiEdit = false;
        }
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        unset($fields['ProviderPropertyID']);

        return $fields;
    }

    public function TuneForm(TBaseForm $form)
    {
        parent::TuneForm($form);

        if (isset($_GET['ProviderID']) && (intval(ArrayVal($_GET, 'ID')) == 0)) {
            $form->Fields["ProviderID"]["Value"] = intval($_GET['ProviderID']);
            $q = new TQuery("select SortIndex from ProviderProperty where ProviderID = " . intval($_GET['ProviderID']) . " order by SortIndex desc limit 1");

            if (!$q->EOF) {
                $form->Fields["SortIndex"]["Value"] = $q->Fields["SortIndex"] + 10;
            } else {
                $form->Fields["SortIndex"]["Value"] = 10;
            }
        }
        $form->Uniques[] = [
            "Fields" => ["ProviderID", "Name"],
            "ErrorMessage" => "Property with this Provider and Name already exists",
        ];
        $form->Uniques[] = [
            "Fields" => ["ProviderID", "Code"],
            "ErrorMessage" => "Property with this Provider and Code already exists",
        ];
        $form->Uniques[] = [
            "Fields" => ["ProviderID", "Kind"],
            "ErrorMessage" => "Property with this Provider and Kind already exists",
            "AllowNulls" => true,
        ];

        if (ArrayVal($_SERVER, 'REMOTE_USER') == 'points') {
            $form->SubmitButtonCaption = "Cancel";
            $form->ReadOnly = true;
        }
        $form->OnCheck = [$this, "checkForm", &$form];
        $form->OnSave = [$this, "formSaved"];
    }

    public function checkForm($form)
    {
        $codes = ['Code', 'DisplayName', 'Balance', 'AccountExpirationDate', 'Login', 'Certificates', 'Rentals', 'DetectedCard'];

        if (in_array($form->Fields['Code']['Value'], $codes)) {
            return $form->Fields['Code']['Error'] = 'Invalid Code. Please choose another.<br> Codes: ' . implode(', ', $codes) . ' - are forbidden.';
        }

        return null;
    }

    public function formSaved()
    {
        TProviderSchema::triggerDatabaseUpdate();
    }

    public function GetImportKeyFields()
    {
        return ["ProviderID", "Code"];
    }
}
