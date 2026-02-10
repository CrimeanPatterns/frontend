<?php

class InputValueType
{

  /**
   * 
   * @var string $Code
   * @access public
   */
  public $Code = null;

  /**
   * 
   * @var string $Value
   * @access public
   */
  public $Value = null;

  /**
   * 
   * @param string $Code
   * @param string $Value
   * @access public
   */
  public function __construct($Code, $Value)
  {
    $this->Code = $Code;
    $this->Value = $Value;
  }

}
