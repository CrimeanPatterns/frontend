<?php

class RegisterAccountRequest {

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
	 * @param int $APIVersion
	 * @param string $Provider
	 * @param InputValueType $Values
	 * @param boolean $ExecuteNow
	 * @param int $Timeout
	 * @param string $RequestID
	 * @access public
	 */
	public function __construct($APIVersion, $Provider, $Values, $ExecuteNow, $Timeout, $RequestID)
	{
		$this->APIVersion = $APIVersion;
		$this->Provider = $Provider;
		$this->Values = $Values;
		$this->ExecuteNow = $ExecuteNow;
		$this->Timeout = $Timeout;
		$this->RequestID = $RequestID;
	}

} 