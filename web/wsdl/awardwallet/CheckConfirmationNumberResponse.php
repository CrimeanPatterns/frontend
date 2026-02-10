<?php

class CheckConfirmationNumberResponse
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
   * @var dateTime $CheckDate
   * @access public
   */
  public $CheckDate = null;

  /**
   * 
   * @var dateTime $RequestDate
   * @access public
   */
  public $RequestDate = null;

  /**
   * 
   * @var string $Itineraries
   * @access public
   */
  public $Itineraries = null;

  /**
   * 
   * @param int $APIVersion
   * @param int $ErrorCode
   * @param string $ErrorMessage
   * @param int $State
   * @param string $Message
   * @param dateTime $CheckDate
   * @param dateTime $RequestDate
   * @param string $Itineraries
   * @access public
   */
  public function __construct($APIVersion, $ErrorCode, $ErrorMessage, $State, $Message, $CheckDate, $RequestDate, $Itineraries)
  {
    $this->APIVersion = $APIVersion;
    $this->ErrorCode = $ErrorCode;
    $this->ErrorMessage = $ErrorMessage;
    $this->State = $State;
    $this->Message = $Message;
    $this->CheckDate = $CheckDate;
    $this->RequestDate = $RequestDate;
    $this->Itineraries = $Itineraries;
  }

}
