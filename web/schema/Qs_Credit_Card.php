<?php
class TQs_Credit_CardSchema extends TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();
        $this->ListClass = QsCreditCardAdminList::class;
        $this->TableName = 'QsCreditCard';
        $this->KeyField = 'QsCreditCardID';
        $this->Fields = [
            'QsCreditCardID' => [
                'Caption' => 'id',
                'Type'    => 'integer',
            ],
            'CardName'       => [
                'Caption' => 'Card',
                'Type'    => 'string',
            ],
            'SUM_Clicks'     => [
                'Caption'  => 'SUM Clicks',
                'Type'     => 'integer',
                'Database' => false,
            ],
            'SUM_Earnings'   => [
                'Caption'  => 'SUM Earnings',
                'Type'     => 'float',
                'Database' => false,
            ],
            'SUM_CPC'        => [
                'Caption'  => 'SUM CPC',
                'Type'     => 'float',
                'Database' => false,
            ],
            'SUM_Approvals'  => [
                'Caption'  => 'SUM Approvals',
                'Type'     => 'string',
                'Database' => false,
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
        $list->SQL = '
            SELECT
                c.*,
                (SELECT SUM(t.Clicks) FROM QsTransaction AS t WHERE t.QsCreditCardID = c.QsCreditCardID) AS SUM_Clicks,
                (SELECT SUM(t.Earnings) FROM QsTransaction AS t WHERE t.QsCreditCardID = c.QsCreditCardID) AS SUM_Earnings,
                (SELECT SUM(t.CPC) FROM QsTransaction AS t WHERE t.QsCreditCardID = c.QsCreditCardID) AS SUM_CPC,
	            (SELECT SUM(t.Approvals) FROM QsTransaction AS t WHERE t.QsCreditCardID = c.QsCreditCardID) AS SUM_Approvals
            FROM ' . $this->TableName . ' c
            WHERE 1
                [Filters]
        ';

        $list->KeyField = $this->KeyField;
        $list->CanAdd = false;
        $list->AllowDeletes = false;
        $list->ShowExport = false;
        $list->ShowImport = false;
        $list->MultiEdit = false;
    }

    function TuneForm(\TBaseForm $form)
    {
        $form->KeyField = $this->KeyField;
    }

    function GetFormFields()
    {
        $arFields = $this->Fields;
        unset($arFields[$this->KeyField]);
        unset($arFields['SUM_Clicks'], $arFields['SUM_Earnings'], $arFields['SUM_CPC'], $arFields['SUM_Approvals']);

        return $arFields;
    }
}
