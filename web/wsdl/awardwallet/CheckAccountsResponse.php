<?php

class CheckAccountsResponse
{

  /**
   * 
   * @var CheckAccountResponse $Responses
   * @access public
   */
  public $Responses = null;

  /**
   * 
   * @param CheckAccountResponse $Responses
   * @access public
   */
  public function __construct($Responses)
  {
    $this->Responses = $Responses;
  }

}
