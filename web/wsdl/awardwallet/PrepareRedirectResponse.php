<?php

class PrepareRedirectResponse
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
   * @var string $Response
   * @access public
   */
  public $Response = null;

  /**
   * 
   * @param int $APIVersion
   * @param int $ErrorCode
   * @param string $ErrorMessage
   * @param string $Response
   * @access public
   */
  public function __construct($APIVersion, $ErrorCode, $ErrorMessage, $Response)
  {
    $this->APIVersion = $APIVersion;
    $this->ErrorCode = $ErrorCode;
    $this->ErrorMessage = $ErrorMessage;
    $this->Response = $Response;
  }

}
