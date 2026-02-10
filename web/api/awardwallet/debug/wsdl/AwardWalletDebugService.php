<?php

include_once('SavePasswordRequest.php');
include_once('SavePasswordResponse.php');


/**
 * AwardWallet debug interface
 * 
 */
class AwardWalletDebugService extends TExtSoapClient
{

  /**
   * 
   * @var array $classmap The defined classes
   * @access private
   */
  private static $classmap = array(
    'SavePasswordRequest' => 'SavePasswordRequest',
    'SavePasswordResponse' => 'SavePasswordResponse');

  /**
   * 
   * @param array $config A array of config values
   * @param string $wsdl The wsdl file to use
   * @access public
   */
  public function __construct(array $options = array(), $wsdl = 'debug.wsdl')
  {
    foreach(self::$classmap as $key => $value)
    {
      if(!isset($options['classmap'][$key]))
      {
        $options['classmap'][$key] = $value;
      }
    }
    
    parent::__construct($wsdl, $options);
  }

  /**
   * Save password to vault
   * 
   * @param SavePasswordRequest $body
   * @access public
   */
  public function SavePassword(SavePasswordRequest $body)
  {
    return $this->__soapCall('SavePassword', array($body));
  }

}
