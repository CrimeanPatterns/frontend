<?php

class PictureType
{

  /**
   * 
   * @var string $Url
   * @access public
   */
  public $Url = null;

  /**
   * 
   * @param string $Url
   * @access public
   */
  public function __construct($Url)
  {
    $this->Url = $Url;
  }

}
