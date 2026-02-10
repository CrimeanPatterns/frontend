<?php

class CheckAccountResponse
{

  /**
   * 
   * @var int $APIVersion
   * @access public
   */
  public $APIVersion;

  /**
   * 
   * @var string $AccountID
   * @access public
   */
  public $AccountID;

  /**
   * 
   * @var string $Provider
   * @access public
   */
  public $Provider;

  /**
   * 
   * @var string $Login
   * @access public
   */
  public $Login;

  /**
   * 
   * @var string $Login2
   * @access public
   */
  public $Login2;

  /**
   * 
   * @var string $Login3
   * @access public
   */
  public $Login3;

  /**
   * 
   * @var int $ErrorCode
   * @access public
   */
  public $ErrorCode;

  /**
   * 
   * @var string $ErrorMessage
   * @access public
   */
  public $ErrorMessage;

  /**
   *
   * @var string $ErrorReason
   * @access public
   */
  public $ErrorReason;

  /**
   *
   * @var string $DebugInfo
   * @access public
   */
  public $DebugInfo;

  /**
   * 
   * @var int $State
   * @access public
   */
  public $State;

  /**
   * 
   * @var string $Message
   * @access public
   */
  public $Message;

  /**
   * 
   * @var string $Question
   * @access public
   */
  public $Question;

  /**
   * 
   * @var float $Balance
   * @access public
   */
  public $Balance;

  /**
   * 
   * @var PropertyType $Properties
   * @access public
   */
  public $Properties;

  /**
   * 
   * @var SubAccountType $SubAccounts
   * @access public
   */
  public $SubAccounts;

  /**
   * 
   * @var dateTime $CheckDate
   * @access public
   */
  public $CheckDate;

  /**
   * 
   * @var dateTime $RequestDate
   * @access public
   */
  public $RequestDate;

  /**
   * 
   * @var string $Mode
   * @access public
   */
  public $Mode;

  /**
   * 
   * @var string $Itineraries
   * @access public
   */
  public $Itineraries;

  /**
   * 
   * @var string $BrowserState
   * @access public
   */
  public $BrowserState;

  /**
   * 
   * @var HistoryRowType $History
   * @access public
   */
  public $History;

  /**
   * 
   * @var int $HistoryVersion
   * @access public
   */
  public $HistoryVersion;

  /**
   * 
   * @var boolean $HistoryCacheValid
   * @access public
   */
  public $HistoryCacheValid;

  /**
   * 
   * @var FileType $Files
   * @access public
   */
  public $Files;

  /**
   * 
   * @var int $FilesVersion
   * @access public
   */
  public $FilesVersion;

  /**
   * 
   * @var boolean $FilesCacheValid
   * @access public
   */
  public $FilesCacheValid;

  /**
   * 
   * @var int $EliteLevel
   * @access public
   */
  public $EliteLevel;

  /**
   * 
   * @var boolean $NoItineraries
   * @access public
   */
  public $NoItineraries;

  /**
   * 
   * @var boolean $NeverExpires
   * @access public
   */
  public $NeverExpires;

  /**
  * @var AnswerType[] $InvalidAnswers
  * @access public
  */
  public $InvalidAnswers;

  /**
   * @var string $Options
   * @access public
   */
  public $Options;

  /**
   * 
   * @param int $APIVersion
   * @param string $AccountID
   * @param string $Provider
   * @param string $Login
   * @param string $Login2
   * @param string $Login3
   * @param int $ErrorCode
   * @param string $ErrorMessage
   * @param string $ErrorReason
   * @param string $DebugInfo
   * @param int $State
   * @param string $Message
   * @param string $Question
   * @param float $Balance
   * @param PropertyType $Properties
   * @param SubAccountType $SubAccounts
   * @param dateTime $CheckDate
   * @param dateTime $RequestDate
   * @param string $Mode
   * @param string $Itineraries
   * @param string $BrowserState
   * @param HistoryRowType $History
   * @param int $HistoryVersion
   * @param boolean $HistoryCacheValid
   * @param FileType $Files
   * @param int $FilesVersion
   * @param boolean $FilesCacheValid
   * @param int $EliteLevel
   * @param boolean $NoItineraries
   * @param boolean $NeverExpires
   * @param boolean $InvalidAnswers
   * @param string $Options
   * @access public
   */
  public function __construct($APIVersion, $AccountID, $Provider, $Login, $Login2, $Login3, $ErrorCode, $ErrorMessage, $ErrorReason, $DebugInfo, $State, $Message, $Question, $Balance, $Properties, $SubAccounts, $CheckDate, $RequestDate, $Mode, $Itineraries, $BrowserState, $History, $HistoryVersion, $HistoryCacheValid, $Files, $FilesVersion, $FilesCacheValid, $EliteLevel, $NoItineraries, $NeverExpires, $InvalidAnswers, $Options)
  {
    $this->APIVersion = $APIVersion;
    $this->AccountID = $AccountID;
    $this->Provider = $Provider;
    $this->Login = $Login;
    $this->Login2 = $Login2;
    $this->Login3 = $Login3;
    $this->ErrorCode = $ErrorCode;
    $this->ErrorMessage = $ErrorMessage;
    $this->ErrorReason = $ErrorReason;
	$this->DebugInfo = $DebugInfo;
    $this->State = $State;
    $this->Message = $Message;
    $this->Question = $Question;
    $this->Balance = $Balance;
    $this->Properties = $Properties;
    $this->SubAccounts = $SubAccounts;
    $this->CheckDate = $CheckDate;
    $this->RequestDate = $RequestDate;
    $this->Mode = $Mode;
    $this->Itineraries = $Itineraries;
    $this->BrowserState = $BrowserState;
    $this->History = $History;
    $this->HistoryVersion = $HistoryVersion;
    $this->HistoryCacheValid = $HistoryCacheValid;
    $this->Files = $Files;
    $this->FilesVersion = $FilesVersion;
    $this->FilesCacheValid = $FilesCacheValid;
    $this->EliteLevel = $EliteLevel;
    $this->NoItineraries = $NoItineraries;
    $this->NeverExpires = $NeverExpires;
    $this->InvalidAnswers = $InvalidAnswers;
    $this->Options = $Options;
  }

}
