<?php

class TQsTransactionStateSchema extends TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();
        $this->ListClass = QsTransactionStateAdminList::class;
        $this->TableName = 'QsTransaction';
        $this->KeyField = 'ClickDate';
        $this->DefaultSort = 'ClickDate';

        $this->Fields = [
            'QsTransactionID' => [
                'Caption' => 'QsTransactionID',
                'Type' => 'integer',
                'Sort' => 'QsTransactionID',
            ],
            'ClickDate' => [
                'Type' => 'string',
                'Sort' => 'ClickDate ASC, ProcessDate ASC',
            ],
            'UserID' => [
                'Caption' => 'UserID',
                'Type' => 'integer',
                'FilterField' => 'qt.UserID',
            ],
            'Card' => [
                'Type' => 'string',
            ],
            'Approvals' => [
                'Type' => 'integer',
                'Options' => [
                    0 => 'No',
                    1 => 'Yes',
                ]
            ],
            'SubAccountFicoState' => [
                'Type' => 'string',
            ],
            'CreditCardState' => [
                'Type' => 'string',
            ],
        ];
    }

    function GetListFields()
    {
        $arFields = $this->Fields;

        return $arFields;
    }

    function TuneList(&$list)
    {
        /* @var $list TBaseList */
        parent::TuneList($list);
        $list->KeyField = $this->KeyField;
        $list->CanAdd = false;
        $list->AllowDeletes = false;
        $list->ShowExport = false;
        $list->ShowImport = false;
        $list->UsePages = true;
        $list->MultiEdit = false;

        $list->SQL = $this->getSqlBy();
    }

    function TuneForm(\TBaseForm $form)
    {
        $form->KeyField = $this->KeyField;
    }

    function GetFormFields()
    {
        $arFields = $this->Fields;
        unset($arFields[$this->KeyField]);

        return $arFields;
    }

    public function getSqlBy()
    {
        return "
            SELECT
                   qt.QsTransactionID, qt.Card, qt.ClickDate, qt.UserID, qt.Approvals, qt.SubAccountFicoState, qt.CreditCardState,
                   u.FirstName, u.LastName
            FROM " . $this->TableName . " qt
            JOIN Usr u ON (qt.UserID = u.UserID)
            WHERE
                   (json_length(qt.SubAccountFicoState) != 0
                OR json_length(qt.CreditCardState) != 0)
                -- AND (qt.SubAccountFicoState IS NOT NULL OR qt.CreditCardState IS NOT NULL)
                [Filters]";
    }
}
