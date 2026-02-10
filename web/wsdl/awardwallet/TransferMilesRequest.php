<?php

class TransferMilesRequest
{

	/**
	 *
	 * @var int $APIVersion
	 * @access public
	 */
	public $APIVersion = null;

	/**
	 *
	 * @var string $SourceProvider
	 * @access public
	 */
	public $SourceProvider = null;

	/**
	 *
	 * @var string $SourceLogin
	 * @access public
	 */
	public $SourceLogin = null;

	/**
	 *
	 * @var string $SourceLogin2
	 * @access public
	 */
	public $SourceLogin2 = null;

	/**
	 *
	 * @var string $SourceLogin3
	 * @access public
	 */
	public $SourceLogin3 = null;

	/**
	 *
	 * @var string $SourcePassword
	 * @access public
	 */
	public $SourcePassword = null;

	/**
	 *
	 * @var string $TargetProvider
	 * @access public
	 */
	public $TargetProvider = null;

	/**
	 *
	 * @var string $TargetAccountNumber
	 * @access public
	 */
	public $TargetAccountNumber = null;

	/**
	 *
	 * @var int $NumberOfMiles
	 * @access public
	 */
	public $NumberOfMiles = null;

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
	 * @param string $SourceProvider
	 * @param string $SourceLogin
	 * @param string $SourceLogin2
	 * @param string $SourceLogin3
	 * @param string $SourcePassword
	 * @param string $TargetProvider
	 * @param string $TargetAccountNumber
	 * @param int $NumberOfMiles
	 * @param boolean $ExecuteNow
	 * @param int $Timeout
	 * @param string $RequestID
	 * @param AnswerType $Answers
	 * @param string $BrowserState
	 * @access public
	 */
	public function __construct($APIVersion, $SourceProvider, $SourceLogin, $SourceLogin2, $SourceLogin3, $SourcePassword, $TargetProvider, $TargetAccountNumber, $NumberOfMiles, $ExecuteNow, $Timeout, $RequestID, $Answers, $BrowserState)
	{
		$this->APIVersion = $APIVersion;
		$this->SourceProvider = $SourceProvider;
		$this->SourceLogin = $SourceLogin;
		$this->SourceLogin2 = $SourceLogin2;
		$this->SourceLogin3 = $SourceLogin3;
		$this->SourcePassword = $SourcePassword;
		$this->TargetProvider = $TargetProvider;
		$this->TargetAccountNumber = $TargetAccountNumber;
		$this->NumberOfMiles = $NumberOfMiles;
		$this->ExecuteNow = $ExecuteNow;
		$this->Timeout = $Timeout;
		$this->RequestID = $RequestID;
		$this->Answers = $Answers;
		$this->BrowserState = $BrowserState;
	}

}
