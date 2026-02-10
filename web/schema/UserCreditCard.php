<?php

class TUserCreditCardSchema extends TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();
        $this->ListClass = UserCreditCardAdminList::class;
        $this->TableName = 'UserCreditCard';
        $this->KeyField = 'UserCreditCardID';
        $this->Fields = [
            'UserCreditCardID'   => [
                'Caption'     => 'ID',
                'Type'        => 'integer',
                'filterWidth' => 30,
                'Sort'        => 'UserCreditCardID DESC',
            ],
            'UserID'             => [
                'Caption'     => 'UserID',
                'Type'        => 'integer',
                'FilterField' => 'ucc.UserID',
            ],
            'CreditCardID'       => [
                'Caption'     => 'CreditCardID',
                'FilterField' => 'ucc.CreditCardID',
                'Type'        => 'integer',
            ],
            'EarliestSeenDate'   => [
                'Caption' => 'EarliestSeenDate',
                'Type'    => 'date',
            ],
            'LastSeenDate'       => [
                'Caption' => 'LastSeenDate',
                'Type'    => 'date',
            ],
            'IsClosed'           => [
                'Type' => 'boolean',
            ],
            'DetectedViaBank'    => [
                'Type' => 'boolean',
            ],
            'DetectedViaCobrand' => [
                'Type' => 'boolean',
            ],
            'DetectedViaQS'      => [
                'Caption' => 'Detected Via QS',
                'Type'    => 'boolean',
            ],
            'DetectedViaEmail' => [
                'Type' => 'boolean',
            ],
        ];
    }

    public function GetListFields()
    {
        $arFields = $this->Fields;

        return $arFields;
    }

    public function TuneList(&$list)
    {
        /* @var $list TBaseList */
        parent::TuneList($list);

        $list->SQL = "
            SELECT
                ucc.*,
                u.UserID, u.FirstName, u.LastName,
                cc.CreditCardID, cc.Name AS CardName
            FROM
                " . $this->TableName . " ucc
            LEFT JOIN Usr u
                ON (ucc.UserID = u.UserID)
            LEFT JOIN CreditCard cc
                ON ucc.CreditCardID = cc.CreditCardID
        ";

        $list->KeyField = $this->KeyField;
        $list->CanAdd = false;
        $list->AllowDeletes = false;
        $list->MultiEdit = false;
        $list->DefaultSort = 'UserCreditCardID';
    }

    public function TuneForm(\TBaseForm $form)
    {
        $form->KeyField = $this->KeyField;
    }

    public function GetFormFields()
    {
        $arFields = $this->Fields;
        unset($arFields[$this->KeyField]);

        return $arFields;
    }
}
