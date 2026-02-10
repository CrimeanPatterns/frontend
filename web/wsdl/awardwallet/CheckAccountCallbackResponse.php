<?php

class CheckAccountCallbackResponse
{

  /**
   * 
   * @var boolean $Success
   * @access public
   */
  public $Success = null;

  /**
   * 
   * @var string $ErrorMessage
   * @access public
   */
  public $ErrorMessage = null;

  /**
   * 
   * @param boolean $Success
   * @param string $ErrorMessage
   * @access public
   */
  public function __construct($Success, $ErrorMessage)
  {
    $this->Success = $Success;
    $this->ErrorMessage = $ErrorMessage;
  }

}
