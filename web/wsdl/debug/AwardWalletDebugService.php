<?php

include_once('GetAccountLogsRequest.php');
include_once('GetAccountLogsResponse.php');
include_once('AccountLogType.php');
include_once('RequestAccountPasswordRequest.php');
include_once('RequestAccountPasswordResponse.php');


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
    'GetAccountLogsRequest' => 'GetAccountLogsRequest',
    'GetAccountLogsResponse' => 'GetAccountLogsResponse',
    'AccountLogType' => 'AccountLogType',
    'RequestAccountPasswordRequest' => 'RequestAccountPasswordRequest',
    'RequestAccountPasswordResponse' => 'RequestAccountPasswordResponse');

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
   * Get logs of last account checks
   * 
   * @param GetAccountLogsRequest $body
   * @access public
   */
  public function GetAccountLogs(GetAccountLogsRequest $body)
  {
    return $this->__soapCall('GetAccountLogs', array($body));
  }

  /**
   * Get logs of last account checks
   * 
   * @param RequestAccountPasswordRequest $body
   * @access public
   */
  public function RequestAccountPassword(RequestAccountPasswordRequest $body)
  {
    return $this->__soapCall('RequestAccountPassword', array($body));
  }

}
