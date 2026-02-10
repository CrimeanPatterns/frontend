<?php

class CheckAccountsRequest
{

  /**
   * 
   * @var CheckAccountRequest $Requests
   * @access public
   */
  public $Requests = null;

  /**
   * 
   * @param CheckAccountRequest $Requests
   * @access public
   */
  public function __construct($Requests)
  {
    $this->Requests = $Requests;
  }

}
