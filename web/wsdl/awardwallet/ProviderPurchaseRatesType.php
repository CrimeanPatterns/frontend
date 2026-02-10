<?php

class ProviderPurchaseRatesType {

	/**
	 * @var string Provider
	 * @access public
	 */
	public $Provider = null;

	/**
	 * @var PurchaseRateType[] $Rates
	 * @access public
	 */
	public $Rates = null;

	/**
	 * @param string $Provider
	 * @param PurchaseRateType[] $Rates
	 */
	public function __construct($Provider, $Rates) {
		$this->Provider = $Provider;
		$this->Rates = $Rates;
	}

}