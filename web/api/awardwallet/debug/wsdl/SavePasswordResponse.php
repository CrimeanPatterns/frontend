<?php

class SavePasswordResponse
{

  /**
   * 
   * @var boolean $Saved
   * @access public
   */
  public $Saved;

  /**
   * 
   * @param boolean $Saved
   * @access public
   */
  public function __construct($Saved)
  {
    $this->Saved = $Saved;
  }

}
