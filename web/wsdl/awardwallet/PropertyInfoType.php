<?php

class PropertyInfoType
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
   * @param string $Code
   * @param string $Name
   * @param int $Kind
   * @access public
   */
  public function __construct($Code, $Name, $Kind)
  {
    $this->Code = $Code;
    $this->Name = $Name;
    $this->Kind = $Kind;
  }

}
