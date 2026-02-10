<?php

class TransferMilesResponse
{

	/**
	 *
	 * @var int $APIVersion
	 * @access public
	 */
	public $APIVersion = null;

	/**
	 *
	 * @var int $ErrorCode
	 * @access public
	 */
	public $ErrorCode = null;

	/**
	 *
	 * @var string $ErrorMessage
	 * @access public
	 */
	public $ErrorMessage = null;

	/**
	 *
	 * @var int $State
	 * @access public
	 */
	public $State = null;

	/**
	 *
	 * @var string $Message
	 * @access public
	 */
	public $Message = null;

	/**
	 *
	 * @var string $RequestID
	 * @access public
	 */
	public $RequestID = null;

	/**
	 *
	 * @var dateTime $ExecuteDate
	 * @access public
	 */
	public $ExecuteDate = null;

	/**
	 *
	 * @var dateTime $RequestDate
	 * @access public
	 */
	public $RequestDate = null;

	/**
	 *
	 * @var string $Question
	 * @access public
	 */
	public $Question = null;

	/**
	 *
	 * @var string $BrowserState
	 * @access public
	 */
	public $BrowserState = null;

	/**
	 * @var AnswerType[] $InvalidAnswers
	 * @access public
	 */
	public $InvalidAnswers = null;

	/**
	 *
	 * @param int $APIVersion
	 * @param int $ErrorCode
	 * @param string $ErrorMessage
	 * @param int $State
	 * @param string $Message
	 * @param string $RequestID
	 * @param dateTime $ExecuteDate
	 * @param dateTime $RequestDate
	 * @param string $Question
	 * @param string $BrowserState
	 * @param boolean $InvalidAnswers
	 * @access public
	 */
	public function __construct($APIVersion, $ErrorCode, $ErrorMessage, $State, $Message, $RequestID, $ExecuteDate, $RequestDate, $Question, $BrowserState, $InvalidAnswers)
	{
		$this->APIVersion = $APIVersion;
		$this->ErrorCode = $ErrorCode;
		$this->ErrorMessage = $ErrorMessage;
		$this->State = $State;
		$this->Message = $Message;
		$this->RequestID = $RequestID;
		$this->ExecuteDate = $ExecuteDate;
		$this->RequestDate = $RequestDate;
		$this->Question = $Question;
		$this->BrowserState = $BrowserState;
		$this->InvalidAnswers = $InvalidAnswers;
	}

}
