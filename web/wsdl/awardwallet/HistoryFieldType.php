<?php

class HistoryFieldType
{

  /**
   * 
   * @var string $Code
   * @access public
   */
  public $Code = null;

  /**
   * 
   * @var string $Name
   * @access public
   */
  public $Name = null;

  /**
   * 
   * @var string $Value
   * @access public
   */
  public $Value = null;

  /**
   * 
   * @param string $Code
   * @param string $Name
   * @param string $Value
   * @access public
   */
  public function __construct($Code, $Name, $Value)
  {
    $this->Code = $Code;
    $this->Name = $Name;
    $this->Value = $Value;
  }

}
