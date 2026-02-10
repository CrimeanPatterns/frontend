<?php

class RequestAccountPasswordResponse
{

  /**
   * 
   * @var boolean $Exists
   * @access public
   */
  public $Exists;

  /**
   * 
   * @param boolean $Exists
   * @access public
   */
  public function __construct($Exists)
  {
    $this->Exists = $Exists;
  }

}
