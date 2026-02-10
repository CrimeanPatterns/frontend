<?php

require_once(__DIR__ . "/../lib/classes/TBaseSchema.php");
require_once(__DIR__ . "/../lib/classes/TTableLinksFieldManager.php");

class TReviewSchema extends TBaseSchema
{

    public function __construct()
    {
        parent::TBaseSchema();
        $this->TableName = 'Review';

        $this->Fields = [
            'ReviewID' => [
                'Type' => 'integer',
                'Required' => false,
                'Caption' => 'ID',
                'DisplayFormat' => 'string',
                'Sort' => 'ReviewID',
                'InplaceEdit' => false,
            ],
            'ProviderID' => [
                'Type' => 'integer',
                'Required' => false,
                'Caption' => 'Provider',
                'DisplayFormat' => 'string',
                'Sort' => 'ProviderID',
                'Options' => ['' => ''] + SQLToArray('SELECT r.ProviderID, p.DisplayName AS Name FROM Review r JOIN Provider p ON (r.ProviderID = p.ProviderID) ORDER BY DisplayName ASC', 'ProviderID', 'Name'),
                'InplaceEdit' => false,
            ],
            'Review' => [
                'Type' => 'string',
                'Required' => false,
                'Caption' => 'Review',
                'DisplayFormat' => 'string',
                'InputType' => 'textarea',
                'Sort' => 'Review',
                'InplaceEdit' => false,
            ],
            'UserID' => [
                'Type' => 'integer',
                'Required' => false,
                'Caption' => 'UserID',
                'Sort' => 'UserID DESC',
                'InplaceEdit' => false,
            ],
            'UpdateDate' => [
                'Type' => 'date',
                'Required' => false,
                'Caption' => 'Update Date',
                'DisplayFormat' => 'string',
                'Sort' => 'UpdateDate DESC',
                'InplaceEdit' => false,
            ],
            'Approved' => [
                'Type' => 'integer',
                'Required' => false,
                'Caption' => 'Approved',
                'DisplayFormat' => 'string',
                'Sort' => 'Approved',
                'Options' => [0 => 'No', 1 => 'Yes'],
                'ReadOnly' => true,
                'InplaceEdit' => true,
            ],
        ];
    }

    function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
    }

    function CreateList($arFields = null)
    {
        if (empty($arFields)) {
            $arFields = $this->Fields;
        }
        $list = parent::CreateList($arFields);

        return $list;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->DefaultSort = 'UpdateDate';
        $list->ShowImport = true;
        $list->AllowDeletes = true;
        $list->CanAdd = false;
        $list->MultiEdit = true;
        $list->InplaceEdit = true;
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        unset($fields['ProviderID'], $fields['UpdateDate'], $fields['UserID']);
        
        return $fields;
    }
}
