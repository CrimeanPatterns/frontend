<?php

class CheckConfirmationNumberRequest
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
   * @var InputValueType $Values
   * @access public
   */
  public $Values = null;

  /**
   *
   * @var string $UserID
   * @access public
   */
  public $UserID = null;

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
   * @var int $Retries
   * @access public
   */
  public $Retries = null;

  /**
   * 
   * @param int $APIVersion
   * @param string $Provider
   * @param InputValueType $Values
   * @param string $UserID
   * @param boolean $CheckNow
   * @param int $Timeout
   * @param int $Priority
   * @param int $Retries
   * @access public
   */
  public function __construct($APIVersion, $Provider, $Values, $UserID, $CheckNow, $Timeout, $Priority, $Retries)
  {
    $this->APIVersion = $APIVersion;
    $this->Provider = $Provider;
    $this->Values = $Values;
    $this->UserID = $UserID;
    $this->CheckNow = $CheckNow;
    $this->Timeout = $Timeout;
    $this->Priority = $Priority;
    $this->Retries = $Retries;
  }

}
