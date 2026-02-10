<?php

class InputType
{

  /**
   * 
   * @var string $Code
   * @access public
   */
  public $Code = null;

  /**
   * 
   * @var string $Title
   * @access public
   */
  public $Title = null;

  /**
   * 
   * @var PropertyInfoType $Options
   * @access public
   */
  public $Options = null;

  /**
   * 
   * @var boolean $Required
   * @access public
   */
  public $Required = null;

  /**
   * 
   * @var string $DefaultValue
   * @access public
   */
  public $DefaultValue = null;

  /**
   * 
   * @param string $Code
   * @param string $Title
   * @param PropertyInfoType $Options
   * @param boolean $Required
   * @param string $DefaultValue
   * @access public
   */
  public function __construct($Code, $Title, $Options, $Required, $DefaultValue)
  {
    $this->Code = $Code;
    $this->Title = $Title;
    $this->Options = $Options;
    $this->Required = $Required;
    $this->DefaultValue = $DefaultValue;
  }

}
