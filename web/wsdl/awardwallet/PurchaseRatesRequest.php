<?php

class PurchaseRatesRequest
{

    /**
     * @var int $APIVersion
     * @access public
     */
    public $APIVersion = null;

    /**
     * @var string $Provider
     * @access public
     */
    public $Provider = null;

    /**
     * @param int $APIVersion
     * @param string $Provider
     * @access public
     */
    public function __construct($APIVersion, $Provider)
    {
      $this->APIVersion = $APIVersion;
      $this->Provider = $Provider;
    }

}
