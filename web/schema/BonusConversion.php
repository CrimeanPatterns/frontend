<?php

class TBonusConversionSchema extends TBaseSchema{

	function __construct(){
		parent::TBaseSchema();
		$this->TableName = "BonusConversion";
		$this->ListClass = "BonusConversionList";
		$this->DefaultSort = 'CreationDate';
		$this->Fields = array(
			'BonusConversionID' => array(
				'Caption' =>	'ID',
				'Type' =>		'integer'
			),
			'Airline' => array(
				'Required' => true,
				'Type' =>	'string'
			),
			'Points' => array(
				'Type' =>	'integer',
				'Required' => true
			),
			'Miles' => array(
				'Type' =>	'integer',
				'Required' => true
			),
			'CreationDate' => array(
				'Type' =>	'date',
				'Required' => true,
				'FilterField' => "bc.CreationDate",
				'IncludeTime' => true,
				"InputAttributes" => " readonly",
			),
			'Processed' => array(
				'Type' =>	'integer',
				'Options' 	=> array(
					'0' =>	'No',
					'1' =>	'Yes'
				),
				'Required' => true
			),
			'Cost' => array(
				'Type' => 'string'
			),
			'UserID' => array(
				'Caption' =>	'UserID',
				'Type' =>		'integer',
				"FilterField" => "u.UserID",
				"InputAttributes" => " readonly",
				"Required" => false,
			),
			'UserName' => array(
				'Caption' => 'User name',
				'Type' =>	'string',
				"InputAttributes" => " readonly",
				"FilterType" => "having",
				"Required" => false,
			),
			'AccountID' => array(
				'Caption' => 'AccountID',
				'FilterField' => 'bc.AccountID',
				'Type' =>	  'integer',
				"InputAttributes" => " readonly",
				"Required" => false,
			),
			'Login' => array(
				'Caption' => "Account Number",
				'Type' => 'string',
				"InputAttributes" => " readonly",
				'FilterField' => 'a.Login',
				"Required" => false,
			),
			'ReferralsIncome' => array(
				'Type' =>	'money',
				'Caption' => 'Referrals Income',
				'Database' => false
			)
		);
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$fields = & $form->Fields;
		unset($fields['BonusConversionID']);
		unset($fields['ReferralsIncome']);

		$fields['UserID']['Database'] = false;
		$fields['AccountID']['Database'] = false;
		$fields['UserName']['Database'] = false;
		$fields['Login']['Database'] = false;

		$account = SQLToArray("
			SELECT
				a.AccountID,
				a.Login,
				concat( u.FirstName, ' ', u.LastName ) as UserName
			FROM BonusConversion bc
			LEFT JOIN Account a ON
				bc.AccountID = a.AccountID
			LEFT JOIN Usr u ON
				bc.UserID = u.UserID
			WHERE bc.BonusConversionID = {$form->ID}
			LIMIT 1
		", 'AccountID', null, true);
		$fields['UserName']['Value'] = $account[0]['UserName'];
		if(isset($account[0]['AccountID'])){
			$fields['Login']['Value'] = $account[0]['Login'];
		}else{
			unset($fields['AccountID']);
			unset($fields['Login']);
		}
	}
}
