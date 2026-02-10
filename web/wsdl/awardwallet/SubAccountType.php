<?php

class SubAccountType
{

  /**
   * 
   * @var float $Balance
   * @access public
   */
  public $Balance = null;

  /**
   * 
   * @var string $DisplayName
   * @access public
   */
  public $DisplayName = null;

  /**
   * 
   * @var string $Code
   * @access public
   */
  public $Code = null;

  /**
   * 
   * @var PropertyType $Properties
   * @access public
   */
  public $Properties = null;

  /**
   * 
   * @var CouponType $Coupons
   * @access public
   */
  public $Coupons = null;

  /**
   * 
   * @var LocationType $Locations
   * @access public
   */
  public $Locations = null;

  /**
   * 
   * @var PictureType $Pictures
   * @access public
   */
  public $Pictures = null;

  /**
   * 
   * @var boolean $NeverExpires
   * @access public
   */
  public $NeverExpires = null;

  /**
   * 
   * @param float $Balance
   * @param string $DisplayName
   * @param string $Code
   * @param PropertyType $Properties
   * @param CouponType $Coupons
   * @param LocationType $Locations
   * @param PictureType $Pictures
   * @param boolean $NeverExpires
   * @access public
   */
  public function __construct($Balance, $DisplayName, $Code, $Properties, $Coupons, $Locations, $Pictures, $NeverExpires)
  {
    $this->Balance = $Balance;
    $this->DisplayName = $DisplayName;
    $this->Code = $Code;
    $this->Properties = $Properties;
    $this->Coupons = $Coupons;
    $this->Locations = $Locations;
    $this->Pictures = $Pictures;
    $this->NeverExpires = $NeverExpires;
  }

}
