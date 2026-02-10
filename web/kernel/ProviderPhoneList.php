<?

class ProviderPhoneList extends TBaseList{
	
	function FormatFields($output = "html"){
		$this->Query->Fields['PhoneAction'] = TProviderPhoneSchema::getPhoneActions($this->Query->Fields['ProviderPhoneID']);
		parent::FormatFields('');
	}

}
