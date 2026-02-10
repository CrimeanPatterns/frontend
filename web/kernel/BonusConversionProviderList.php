<?php

class BonusConversionProviderList extends TBaseList
{
	public function __construct($table, $fields, $defaultSort){
		parent::__construct($table, $fields, $defaultSort);

		$this->CanAdd = true;
		$this->InplaceEdit = true;
	}

	public function FormatFields($output = "html")
	{
		parent::FormatFields();

        $this->Query->Fields['PurchaseLink'] = empty($this->Query->Fields['PurchaseLink']) ?
            'n/a' :
            "<a href='{$this->Query->Fields['PurchaseLink']}' target='_blank'>Link</a>";
	}
}
