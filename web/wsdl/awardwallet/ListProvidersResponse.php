<?php

class ListProvidersResponse
{

  /**
   * 
   * @var int $APIVersion
   * @access public
   */
  public $APIVersion = null;

  /**
   * 
   * @var int $ErrorCode
   * @access public
   */
  public $ErrorCode = null;

  /**
   * 
   * @var string $ErrorMessage
   * @access public
   */
  public $ErrorMessage = null;

  /**
   * 
   * @var ProviderInfoType $Providers
   * @access public
   */
  public $Providers = null;

  /**
   * 
   * @param int $APIVersion
   * @param int $ErrorCode
   * @param string $ErrorMessage
   * @param ProviderInfoType $Providers
   * @access public
   */
  public function __construct($APIVersion, $ErrorCode, $ErrorMessage, $Providers)
  {
    $this->APIVersion = $APIVersion;
    $this->ErrorCode = $ErrorCode;
    $this->ErrorMessage = $ErrorMessage;
    $this->Providers = $Providers;
  }

}
