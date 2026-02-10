<?php

class PurchaseMilesRequest {

	/**
	 *
	 * @var int $APIVersion
	 * @access public
	 */
	public $APIVersion = null;

	/**
	 *
	 * @var string $Provider
	 * @access public
	 */
	public $Provider = null;

	/**
	 *
	 * @var InputValueType $Values
	 * @access public
	 */
	public $Values = null;

	/**
	 *
	 * @var int $NumberOfMiles
	 * @access public
	 */
	public $NumberOfMiles = null;

	/**
	 *
	 * @var int $CreditCard
	 * @access public
	 */
	public $CreditCard = null;

	/**
	 *
	 * @var boolean $ExecuteNow
	 * @access public
	 */
	public $ExecuteNow = null;

	/**
	 *
	 * @var int $Timeout
	 * @access public
	 */
	public $Timeout = null;

	/**
	 *
	 * @var string $RequestID
	 * @access public
	 */
	public $RequestID = null;

	/**
	 *
	 * @var AnswerType $Answers
	 * @access public
	 */
	public $Answers = null;

	/**
	 *
	 * @var string $BrowserState
	 * @access public
	 */
	public $BrowserState = null;

	/**
	 *
	 * @param int $APIVersion
	 * @param string $Provider
	 * @param InputValueType $Values
	 * @param int $NumberOfMiles
	 * @param boolean $ExecuteNow
	 * @param int $Timeout
	 * @param string $RequestID
	 * @param AnswerType $Answers
	 * @param string $BrowserState
	 * @access public
	 */
	public function __construct($APIVersion, $Provider, $Values, $NumberOfMiles, $CreditCard, $ExecuteNow, $Timeout, $RequestID, $Answers, $BrowserState)
	{
		$this->APIVersion = $APIVersion;
		$this->Provider = $Provider;
		$this->Values = $Values;
		$this->NumberOfMiles = $NumberOfMiles;
		$this->CreditCard = $CreditCard;
		$this->ExecuteNow = $ExecuteNow;
		$this->Timeout = $Timeout;
		$this->RequestID = $RequestID;
		$this->Answers = $Answers;
		$this->BrowserState = $BrowserState;
	}

} 