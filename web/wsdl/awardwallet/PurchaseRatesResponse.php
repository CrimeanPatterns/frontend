<?php

class PurchaseRatesResponse
{

    /**
     * @var int $APIVersion
     * @access public
     */
    public $APIVersion = null;

    /**
     * @var int $ErrorCode
     * @access public
     */
    public $ErrorCode = null;

    /**
     * @var string $ErrorMessage
     * @access public
     */
    public $ErrorMessage = null;

    /**
     * @var ProviderPurchaseRatesType[] $ProviderRate
     * @access public
     */
    public $ProviderRate = null;

    /**
     * @param int $APIVersion
     * @param int $ErrorCode
     * @param string $ErrorMessage
     * @param ProviderPurchaseRatesType[] $ProviderRate
     * @access public
     */
    public function __construct($APIVersion, $ErrorCode, $ErrorMessage, $ProviderRate)
    {
      $this->APIVersion = $APIVersion;
      $this->ErrorCode = $ErrorCode;
      $this->ErrorMessage = $ErrorMessage;
      $this->ProviderRate = $ProviderRate;
    }

}
