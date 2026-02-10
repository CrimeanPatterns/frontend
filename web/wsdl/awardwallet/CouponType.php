<?php

class CouponType
{

  /**
   * 
   * @var string $Id
   * @access public
   */
  public $Id = null;

  /**
   * 
   * @var string $File
   * @access public
   */
  public $File = null;

  /**
   * 
   * @var string $Caption
   * @access public
   */
  public $Caption = null;

  /**
   * 
   * @var boolean $Used
   * @access public
   */
  public $Used = null;

  /**
   * 
   * @var date $ExpiresAt
   * @access public
   */
  public $ExpiresAt = null;

  /**
   * 
   * @var date $PurchasedAt
   * @access public
   */
  public $PurchasedAt = null;

  /**
   * 
   * @var string $Status
   * @access public
   */
  public $Status = null;

  /**
   * 
   * @param string $Id
   * @param string $File
   * @param string $Caption
   * @param boolean $Used
   * @param date $ExpiresAt
   * @param date $PurchasedAt
   * @param string $Status
   * @access public
   */
  public function __construct($Id, $File, $Caption, $Used, $ExpiresAt, $PurchasedAt, $Status)
  {
    $this->Id = $Id;
    $this->File = $File;
    $this->Caption = $Caption;
    $this->Used = $Used;
    $this->ExpiresAt = $ExpiresAt;
    $this->PurchasedAt = $PurchasedAt;
    $this->Status = $Status;
  }

}
