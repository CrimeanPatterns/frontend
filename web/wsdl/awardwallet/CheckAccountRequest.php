<?php

class CheckAccountRequest
{

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
   * @var string $Login
   * @access public
   */
  public $Login = null;

  /**
   * 
   * @var string $Login2
   * @access public
   */
  public $Login2 = null;

  /**
   * 
   * @var string $Login3
   * @access public
   */
  public $Login3 = null;

  /**
   * 
   * @var string $Password
   * @access public
   */
  public $Password = null;

  /**
   * 
   * @var boolean $CheckNow
   * @access public
   */
  public $CheckNow = null;

  /**
   * 
   * @var int $Timeout
   * @access public
   */
  public $Timeout = null;

  /**
   * 
   * @var int $Priority
   * @access public
   */
  public $Priority = null;

  /**
   * 
   * @var string $CallbackURL
   * @access public
   */
  public $CallbackURL = null;

  /**
   * 
   * @var int $Retries
   * @access public
   */
  public $Retries = null;

  /**
   * 
   * @var boolean $ParseItineraries
   * @access public
   */
  public $ParseItineraries = null;

  /**
   * 
   * @var string $UserID
   * @access public
   */
  public $UserID = null;

  /**
   * 
   * @var string $AccountID
   * @access public
   */
  public $AccountID = null;

  /**
   * 
   * @var MarkUsedCouponType $MarkUsedCoupons
   * @access public
   */
  public $MarkUsedCoupons = null;

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
   * @var boolean $ParseHistory
   * @access public
   */
  public $ParseHistory = null;

  /**
   * 
   * @var int $HistoryVersion
   * @access public
   */
  public $HistoryVersion = null;

  /**
   * 
   * @var date $HistoryLastDate
   * @access public
   */
  public $HistoryLastDate = null;

  /**
   * 
   * @var boolean $ParseFiles
   * @access public
   */
  public $ParseFiles = null;

  /**
   * 
   * @var int $FilesVersion
   * @access public
   */
  public $FilesVersion = null;

  /**
   * 
   * @var date $FilesLastDate
   * @access public
   */
  public $FilesLastDate = null;

  /**
   * 
   * @var string $Options
   * @access public
   */
  public $Options = null;

  /**
   * 
   * @param int $APIVersion
   * @param string $Provider
   * @param string $Login
   * @param string $Login2
   * @param string $Login3
   * @param string $Password
   * @param boolean $CheckNow
   * @param int $Timeout
   * @param int $Priority
   * @param string $CallbackURL
   * @param int $Retries
   * @param boolean $ParseItineraries
   * @param string $UserID
   * @param string $AccountID
   * @param MarkUsedCouponType $MarkUsedCoupons
   * @param AnswerType $Answers
   * @param string $BrowserState
   * @param boolean $ParseHistory
   * @param int $HistoryVersion
   * @param date $HistoryLastDate
   * @param boolean $ParseFiles
   * @param int $FilesVersion
   * @param date $FilesLastDate
   * @param string $Options
   * @access public
   */
  public function __construct($APIVersion, $Provider, $Login, $Login2, $Login3, $Password, $CheckNow, $Timeout, $Priority, $CallbackURL, $Retries, $ParseItineraries, $UserID, $AccountID, $MarkUsedCoupons, $Answers, $BrowserState, $ParseHistory, $HistoryVersion, $HistoryLastDate, $ParseFiles, $FilesVersion, $FilesLastDate, $Options)
  {
    $this->APIVersion = $APIVersion;
    $this->Provider = $Provider;
    $this->Login = $Login;
    $this->Login2 = $Login2;
    $this->Login3 = $Login3;
    $this->Password = $Password;
    $this->CheckNow = $CheckNow;
    $this->Timeout = $Timeout;
    $this->Priority = $Priority;
    $this->CallbackURL = $CallbackURL;
    $this->Retries = $Retries;
    $this->ParseItineraries = $ParseItineraries;
    $this->UserID = $UserID;
    $this->AccountID = $AccountID;
    $this->MarkUsedCoupons = $MarkUsedCoupons;
    $this->Answers = $Answers;
    $this->BrowserState = $BrowserState;
    $this->ParseHistory = $ParseHistory;
    $this->HistoryVersion = $HistoryVersion;
    $this->HistoryLastDate = $HistoryLastDate;
    $this->ParseFiles = $ParseFiles;
    $this->FilesVersion = $FilesVersion;
    $this->FilesLastDate = $FilesLastDate;
    $this->Options = $Options;
  }

}
