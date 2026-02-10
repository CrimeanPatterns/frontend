<?php

class AccountLogType
{

  /**
   * 
   * @var dateTime $LogDate
   * @access public
   */
  public $LogDate;

  /**
   *
   * @var string $Login
   * @access public
   */
  public $Login;

  /**
   * 
   * @var string $Zip
   * @access public
   */
  public $Zip;

  /**
   * 
   * @param dateTime $LogDate
   * @param string $Login
   * @param string $Zip
   * @access public
   */
  public function __construct($LogDate, $Login, $Zip)
  {
    $this->LogDate = $LogDate;
    $this->Login = $Login;
    $this->Zip = $Zip;
  }

}
