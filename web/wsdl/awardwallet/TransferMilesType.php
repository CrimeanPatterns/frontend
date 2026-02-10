<?php

class TransferMilesType
{

  /**
   * 
   * @var string $TargetProvider
   * @access public
   */
  public $TargetProvider = null;

  /**
   * 
   * @var string $TargetAccountNumber
   * @access public
   */
  public $TargetAccountNumber = null;

  /**
   * 
   * @var int $NumberOfMiles
   * @access public
   */
  public $NumberOfMiles = null;

  /**
   * 
   * @param string $TargetProvider
   * @param string $TargetAccountNumber
   * @param int $NumberOfMiles
   * @access public
   */
  public function __construct($TargetProvider, $TargetAccountNumber, $NumberOfMiles)
  {
    $this->TargetProvider = $TargetProvider;
    $this->TargetAccountNumber = $TargetAccountNumber;
    $this->NumberOfMiles = $NumberOfMiles;
  }

}
