<?php

class MarkUsedCouponType
{

  /**
   * 
   * @var boolean $Used
   * @access public
   */
  public $Used = null;

  /**
   * 
   * @var string $Id
   * @access public
   */
  public $Id = null;

  /**
   * 
   * @param boolean $Used
   * @param string $Id
   * @access public
   */
  public function __construct($Used, $Id)
  {
    $this->Used = $Used;
    $this->Id = $Id;
  }

}
