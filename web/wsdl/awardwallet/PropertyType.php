<?php

class PropertyType
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
   * @var int $Kind
   * @access public
   */
  public $Kind = null;

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
   * @param int $Kind
   * @param string $Value
   * @access public
   */
  public function __construct($Code, $Name, $Kind, $Value)
  {
    $this->Code = $Code;
    $this->Name = $Name;
    $this->Kind = $Kind;
    $this->Value = $Value;
  }

}
