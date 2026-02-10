<?php

class PurchaseRateType {

	/**
	 *
	 * @var int $Quantity
	 * @access public
	 */
	public $Quantity = null;

	/**
	 *
	 * @var float $Rate
	 * @access public
	 */
	public $Rate = null;

	/**
	 *
	 * @var string $Currency
	 * @access public
	 */
	public $Currency = null;

	/**
	 * @param int $Quantity
	 * @param float $Rate
	 * @param string $Currency
	 * @access public
	 */
	public function __construct($Quantity, $Rate, $Currency) {
		$this->Quantity = $Quantity;;
		$this->Rate = $Rate;
		$this->Currency = $Currency;
	}

}