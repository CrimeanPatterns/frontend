<?php

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\CreditCardFee;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;

class TAbInvoiceSchema extends TBaseSchema
{
    /**
     * @var int
     */
    private $defaultBooker;

    public function __construct()
    {
        parent::TBaseSchema();
        $this->TableName = 'AbInvoice';
        $this->ListClass = 'AbInvoiceList';
        $this->KeyField = $this->TableName . 'ID';
        $this->DefaultSort = $this->KeyField;
        $this->Fields = [
            $this->KeyField => [
                'Caption' => 'Invoice ID',
                'Type' => 'integer',
                'Required' => true,
                'Database' => true,
                'AllowFilters' => true,
                'filterWidth' => 40,
                'FilterField' => 'i.AbInvoiceID',
            ],
            'AbRequestID' => [
                'Caption' => 'Request ID',
                'Type' => 'integer',
                'Required' => true,
                'Database' => true,
                'AllowFilters' => true,
                'filterWidth' => 40,
                'FilterField' => 'r.AbRequestID',
            ],
            'ContactName' => [
                'Caption' => 'Client',
                'Type' => 'string',
                'Required' => true,
                'CheckScripts' => true,
                'filterWidth' => 50,
                'FilterField' => 'r.ContactName',
            ],
            'Segments' => [
                'Caption' => 'Route',
                'Type' => 'string',
                'Required' => true,
                'CheckScripts' => true,
                'AllowFilters' => true,
                'Database' => true,
                'filterWidth' => 40,
                'FilterField' => 's.Segments',
            ],
            'Total' => [
                'Caption' => 'Invoice Amount',
                'Type' => 'string',
                'AllowFilters' => false,
            ],
            'TransactionID' => [
                'Caption' => 'Transaction',
                'Type' => 'string',
                'FilterField' => 'i.TransactionID',
            ],
        ];
        $this->defaultBooker = getSymfonyContainer()->get(DefaultBookerParameter::class)->get();
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->title = 'Booking invoices';
        $list->MultiEdit = true;
        $list->ShowFilters = true;
        $list->ShowImport = false;
        $list->ShowExport = true;
        $list->AllowDeletes = false;
        $list->ReadOnly = false;
        $list->ShowEditors = true;
        $list->CanAdd = false;
        $list->DefaultSort = 'AbInvoiceID';
        $list->Fields['AbInvoiceID']['FilterField'] = 'i.AbInvoiceID';
        $list->SQL = "
		SELECT 
          i.AbInvoiceID, 
          i.TransactionID, 
          r.AbRequestID, 
          r.ContactName, 
          s.Segments, 
          CONCAT('$', t.Total) AS Total
        FROM 
          AbInvoice i 
          JOIN AbMessage m ON m.AbMessageID = i.MessageID 
          JOIN AbRequest r ON r.AbRequestID = m.RequestID 
          JOIN (
            SELECT 
              RequestID, 
              GROUP_CONCAT(
                IF(RoundTrip = 1, CONCAT_WS(', ', CONCAT_WS(' - ', Dep, Arr), CONCAT_WS(' - ', Arr, Dep)), CONCAT_WS(' - ', Dep, Arr))
                ORDER BY 
                  Priority ASC SEPARATOR ', '
              ) AS Segments 
            FROM 
              AbSegment 
            GROUP BY 
              RequestID
          ) s ON s.RequestID = r.AbRequestID 
          JOIN (
            SELECT 
              AbInvoiceID, 
              SUM(
                IF(
                  Type <> " . CreditCardFee::TYPE . ", 
                  ROUND(
                    Price * Quantity - (
                      Price * Quantity * COALESCE(Discount, 0) / 100
                    ), 
                    2
                  ), 
                  0
                )
              ) AS Total 
            FROM 
              AbInvoiceItem 
            GROUP BY 
              AbInvoiceID
          ) t ON t.AbInvoiceID = i.AbInvoiceID
          JOIN AbBookerInfo bi ON bi.UserID = r.BookerUserID
		WHERE
			r.BookerUserID = " . $this->defaultBooker . "
			AND i.Status = " . AbInvoice::STATUS_PAID . "
			[Filters]
		";
    }

    public function TuneForm(TBaseForm $form)
    {
    }

    public function Delete()
    {
    }
}
