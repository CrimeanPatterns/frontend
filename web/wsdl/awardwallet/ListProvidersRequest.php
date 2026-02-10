<?php

class ListProvidersRequest
{

  /**
   * 
   * @var int $APIVersion
   * @access public
   */
  public $APIVersion = null;

  /**
  *
  * @var int $Type
  * @access public
  */
  public $Type = null;

  /**
   * 
   * @param int $APIVersion
   * @access public
   */
  public function __construct($APIVersion, $Type)
  {
    $this->APIVersion = $APIVersion;
    $this->Type = $Type;
  }

}
