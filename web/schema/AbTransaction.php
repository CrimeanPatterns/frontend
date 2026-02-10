<?php

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\CreditCardFee;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;

class TAbTransactionSchema extends TBaseSchema
{
    /**
     * @var int
     */
    private $defaultBooker;

    public function __construct()
    {
        parent::TBaseSchema();
        $this->TableName = 'AbTransaction';
        $this->ListClass = 'AbTransactionList';
        $this->KeyField = $this->TableName . 'ID';
        $this->DefaultSort = $this->KeyField;
        $this->Fields = [
            $this->KeyField => [
                'Caption' => 'id',
                'Type' => 'integer',
                'Required' => true,
                'InputAttributes' => ' readonly',
                'filterWidth' => 30,
            ],
            'ProcessDate' => [
                'Caption' => 'Date',
                'Type' => 'datetime',
                'InputAttributes' => ' readonly',
                'IncludeTime' => true,
            ],
            'Total' => [
                'Caption' => 'Invoice Amount',
                'Type' => 'string',
                'AllowFilters' => false,
            ],
            'Processed' => [
                'Caption' => 'Processed',
                'Type' => 'boolean',
            ],
        ];
        $this->defaultBooker = getSymfonyContainer()->get(DefaultBookerParameter::class)->get();
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->title = 'Booking transactions';
        $list->AllowDeletes = false;
        $list->ReadOnly = false;
        $list->ShowEditors = true;
        $list->ShowFilters = true;
        $list->MultiEdit = true;
        $list->ShowImport = false;
        $list->ShowExport = false;
        $list->CanAdd = false;
        $list->PageSizes['2000'] = '2000';
        $list->Fields['AbTransactionID']['FilterField'] = 'tr.AbTransactionID';

        $list->SQL = "
            SELECT DISTINCT 
              tr.AbTransactionID,
              tr.ProcessDate,
              tr.Processed,
              CONCAT('$', t.Total) AS Total
            FROM 
              AbTransaction tr
              JOIN AbInvoice i ON i.TransactionID = tr.AbTransactionID
              JOIN AbMessage m ON m.AbMessageID = i.MessageID 
              JOIN AbRequest r ON r.AbRequestID = m.RequestID 
              JOIN (
                SELECT 
                  t.AbTransactionID, 
                  SUM(
                    IF(
                      it.Type <> " . CreditCardFee::TYPE . ", 
                      ROUND(
                        it.Price * it.Quantity - (
                          it.Price * it.Quantity * COALESCE(it.Discount, 0) / 100
                        ), 
                        2
                      ), 
                      0
                    )
                  ) AS Total 
                FROM 
                  AbInvoiceItem it
                  JOIN AbInvoice i ON i.AbInvoiceID = it.AbInvoiceID
                  JOIN AbTransaction t ON t.AbTransactionID = i.TransactionID 
                GROUP BY 
                  t.AbTransactionID
              ) t ON t.AbTransactionID = tr.AbTransactionID
              JOIN AbBookerInfo bi ON bi.UserID = r.BookerUserID
            WHERE
                r.BookerUserID = " . $this->defaultBooker . "
                AND i.Status = " . AbInvoice::STATUS_PAID . "
		";
    }

    public function TuneForm(TBaseForm $form)
    {
    }

    public function Delete()
    {
    }

    public function GetFormFields()
    {
        $arFields = $this->Fields;
        unset($arFields['AbTransactionID']);
        unset($arFields['ProcessDate']);
        unset($arFields['Total']);

        return $arFields;
    }
}
