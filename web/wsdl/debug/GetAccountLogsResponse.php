<?php

class GetAccountLogsResponse
{

  /**
   * 
   * @var AccountLogType $Logs
   * @access public
   */
  public $Logs;

  /**
   * 
   * @param AccountLogType $Logs
   * @access public
   */
  public function __construct($Logs)
  {
    $this->Logs = $Logs;
  }

}
