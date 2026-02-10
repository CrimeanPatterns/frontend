<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ALoginov
 * Date: 08.07.13
 * Time: 18:45
 * To change this template use File | Settings | File Templates.
 */

class TIncomeTransactionSchema extends TBaseSchema {

	function __construct(){
		parent::TBaseSchema();
		$this->TableName = 'IncomeTransaction';
		$this->ListClass = 'TIncomeTransactionList';

		$this->Fields = [
			"IncomeTransactionID" => [
				"Caption" => "id",
				"Type" => "integer",
				"Required" => True,
				"InputAttributes" => " readonly",
				"filterWidth" => 30,
				'FilterField' => 't2.IncomeTransactionID'
			],
			'Date' =>[
				"Caption" 	=> "Date",
				"Type" 		=> "date",
				"Required" => True,
				'FilterField' => "t3.Date"
			],
			'Revenue' => [
				'Type' => 'string',
				'Caption' => 'Price',
				"InputAttributes" => " readonly",
				'FilterField' => "t3.Revenue"
			],
			'Fee' => [
				'Type' => 'string',
				'Caption' => 'Fee',
				"InputAttributes" => " readonly",
				'FilterField' => 't3.Fee'
			],
			'Income' => [
				'Type' => 'string',
				'Caption' => 'Income',
				"InputAttributes" => " readonly",
				'FilterField' => 't3.Income',
			],
			'Processed' => [
				"Caption" 	=> "Processed",
				"Type" 		=> "boolean",
				'FilterField' => 't3.Processed'
			],
			'Description' => [
				"Caption" 	=> "Description",
				"Type" 		=> "string",
				'FilterField' => 't3.Description',
				"InputType" => "textarea",
				"Size" => 2000,
			]
		];
	}

	function TuneList( &$list ) {
		parent::TuneList($list);

        $paymenttypeAppstore = PAYMENTTYPE_APPSTORE;
        $cartItemOneCart = CART_ITEM_ONE_CARD;
		$list->SQL = "
            SELECT
                t3.*,
                ROUND(TRUNCATE((t3.RawRevenue - t3.RawFee), 3) + 0.0001, 2) AS Income
            FROM (
                SELECT
                    t2.IncomeTransactionID,
                    t2.Date,
                    t2.Processed,
                    t2.Description,
                    SUM(t2.CartRevenue) as RawRevenue,
                    ROUND(TRUNCATE(SUM(t2.CartRevenue), 3) + 0.0001, 2) AS Revenue,
                    SUM(t2.CartFee) as RawFee,
                    ROUND(TRUNCATE(SUM(t2.CartFee), 3) + 0.0001, 2) AS Fee
                FROM (
                    SELECT
                        t1.*,
                        ((CASE WHEN t1.CartRevenue > 0 THEN (CASE WHEN t1.PaymentType = {$paymenttypeAppstore} THEN (t1.CartRevenue - 0.01) * 0.30 ELSE t1.CartRevenue * 0.029 + 0.30 END) ELSE 0 END) + t1.CartOneCardFee) as CartFee
                    FROM(
                        SELECT
                            it.IncomeTransactionID,
                            it.Date,
                            it.Processed,
                            it.Description,
                            c.PaymentType,
                            SUM(ci.Price * ci.Cnt * ((100 - ci.Discount) / 100)) AS CartRevenue,
                            SUM(CASE WHEN ci.TypeID = {$cartItemOneCart} THEN 1.5 * ci.Cnt ELSE 0 END) AS CartOneCardFee
                        FROM IncomeTransaction it
                        JOIN Cart c ON c.IncomeTransactionID = it.IncomeTransactionID
                        JOIN CartItem ci ON c.CartID = ci.CartID
                        WHERE
                            ci.Cnt > 0 AND ci.Discount < 100 AND (ci.Price <> 0 OR ci.TypeID = 7) AND
                            c.IncomeTransactionID IS NOT NULL AND
                            c.PayDate IS NOT NULL
                        GROUP BY
                            it.IncomeTransactionID,
                            it.Date,
                            it.Processed,
                            it.Description,
                            c.PaymentType
                    ) t1
                ) t2
                GROUP BY
                    t2.IncomeTransactionID,
                    t2.Date,
                    t2.Processed,
                    t2.Description
            ) t3
            WHERE 1 = 1
			[Filters]";

		$list->AllowDeletes = false;
		$list->ReadOnly = false;
		$list->ShowEditors = true;
		$list->ShowFilters = true;
		$list->MultiEdit = true;
		$list->ShowImport = false;
		$list->ShowExport = false;
		$list->CanAdd = false;
		$list->PageSizes['1000'] = '1000';
		$list->PageSizes['2000'] = '2000';
		$list->PageSize = 1000;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$fields = & $form->Fields;

		$fields['Revenue']['Database'] = false;
		$fields['Fee']['Database'] = false;
		$fields['Income']['Database'] = false;

        $paymenttypeAppstore = PAYMENTTYPE_APPSTORE;
        $cartItemOneCart = CART_ITEM_ONE_CARD;
		$transaction = SQLToArray("
            SELECT
                t3.*,
                ROUND(TRUNCATE((t3.RawRevenue - t3.RawFee), 3) + 0.0001, 2) AS Income
            FROM (
                SELECT
                    t2.*,
                    SUM(t2.CartRevenue) as RawRevenue,
                    ROUND(TRUNCATE(SUM(t2.CartRevenue), 3) + 0.0001, 2) AS Revenue,
                    SUM(t2.CartFee) as RawFee,
                    ROUND(TRUNCATE(SUM(t2.CartFee), 3) + 0.0001, 2) AS Fee
                FROM (
                    SELECT
                        t1.*,
                        ((CASE WHEN t1.CartRevenue > 0 THEN (CASE WHEN t1.PaymentType = {$paymenttypeAppstore} THEN (t1.CartRevenue - 0.01) * 0.30 ELSE t1.CartRevenue * 0.029 + 0.30 END) ELSE 0 END) + t1.CartOneCardFee) as CartFee
                    FROM(
                        SELECT
                            it.IncomeTransactionID,
                            it.Date,
                            it.Processed,
                            it.Description,
                            c.PaymentType,
                            SUM(ci.Price * ci.Cnt * ((100 - ci.Discount) / 100)) AS CartRevenue,
                            SUM(CASE WHEN ci.TypeID = {$cartItemOneCart} THEN 1.5 * ci.Cnt ELSE 0 END) AS CartOneCardFee
                        FROM IncomeTransaction it
                        JOIN Cart c ON c.IncomeTransactionID = it.IncomeTransactionID
                        JOIN CartItem ci ON c.CartID = ci.CartID
                        WHERE
                            ci.Cnt > 0 AND ci.Discount < 100 AND (ci.Price <> 0 OR ci.TypeID = 7) AND
                            c.IncomeTransactionID = {$form->ID} AND
                            c.PayDate IS NOT NULL
                        GROUP BY
                            it.IncomeTransactionID,
                            it.Date,
                            it.Processed,
                            it.Description,
                            c.PaymentType
                    ) t1
                ) t2
                GROUP BY
                    t2.IncomeTransactionID
            ) t3
		", 'IncomeTransactionID', null, true);
		if(!empty($transaction)){
			foreach(array('Revenue', 'Income', 'Fee') as $key){
				$fields[$key]['Value'] = number_format_localized($transaction[0][$key], 2);
			}
		}
	}

}
