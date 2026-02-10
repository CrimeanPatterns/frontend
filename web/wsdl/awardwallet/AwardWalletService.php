<?php

include_once('CheckAccountResponse.php');
include_once('ProviderInfoType.php');
include_once('HistoryFieldType.php');
include_once('HistoryRowType.php');
include_once('FileType.php');
include_once('InputType.php');
include_once('PropertyType.php');
include_once('PropertyInfoType.php');
include_once('AnswerType.php');
include_once('SubAccountType.php');
include_once('CouponType.php');
include_once('LocationType.php');
include_once('PictureType.php');
include_once('MarkUsedCouponType.php');
include_once('CheckAccountRequest.php');
include_once('ListProvidersRequest.php');
include_once('ListProvidersResponse.php');
include_once('CheckAccountsRequest.php');
include_once('CheckAccountsResponse.php');
include_once('PrepareRedirectRequest.php');
include_once('PrepareRedirectResponse.php');
include_once('CheckAccountCallbackResponse.php');
include_once('PartnerType.php');
include_once('QueueInfo.php');
include_once('InputValueType.php');
include_once('CheckConfirmationNumberRequest.php');
include_once('CheckConfirmationNumberResponse.php');
include_once('TransferMilesType.php');
include_once('TransferMilesRequest.php');
include_once('TransferMilesResponse.php');
include_once('RegisterAccountRequest.php');
include_once('RegisterAccountResponse.php');
include_once('TransferPartnerType.php');
include_once('PurchaseMilesRequest.php');
include_once('PurchaseMilesResponse.php');
include_once('PurchaseRatesRequest.php');
include_once('PurchaseRatesResponse.php');
include_once('PurchaseRateType.php');
include_once('ProviderPurchaseRatesType.php');


/**
 * General awardwallet interface
 * 
 */
class AwardWalletService extends TExtSoapClient
{

  /**
   * 
   * @var array $classmap The defined classes
   * @access private
   */
  private static $classmap = array(
    'CheckAccountResponse' => '\\CheckAccountResponse',
    'ProviderInfoType' => '\\ProviderInfoType',
    'HistoryFieldType' => '\\HistoryFieldType',
    'HistoryRowType' => '\\HistoryRowType',
    'FileType' => '\\FileType',
    'InputType' => '\\InputType',
    'PropertyType' => '\\PropertyType',
    'PropertyInfoType' => '\\PropertyInfoType',
    'AnswerType' => '\\AnswerType',
    'SubAccountType' => '\\SubAccountType',
    'CouponType' => '\\CouponType',
    'LocationType' => '\\LocationType',
    'PictureType' => '\\PictureType',
    'MarkUsedCouponType' => '\\MarkUsedCouponType',
    'CheckAccountRequest' => '\\CheckAccountRequest',
    'ListProvidersRequest' => '\\ListProvidersRequest',
    'ListProvidersResponse' => '\\ListProvidersResponse',
    'CheckAccountsRequest' => '\\CheckAccountsRequest',
    'CheckAccountsResponse' => '\\CheckAccountsResponse',
    'PrepareRedirectRequest' => '\\PrepareRedirectRequest',
    'PrepareRedirectResponse' => '\\PrepareRedirectResponse',
    'CheckAccountCallbackResponse' => '\\CheckAccountCallbackResponse',
    'PartnerType' => '\\PartnerType',
    'QueueInfo' => '\\QueueInfo',
    'InputValueType' => '\\InputValueType',
    'CheckConfirmationNumberRequest' => '\\CheckConfirmationNumberRequest',
    'CheckConfirmationNumberResponse' => '\\CheckConfirmationNumberResponse',
    'TransferMilesType' => '\\TransferMilesType',
    'TransferMilesRequest' => '\\TransferMilesRequest',
    'TransferMilesResponse' => '\\TransferMilesResponse',
    'RegisterAccountRequest' => '\\RegisterAccountRequest',
    'RegisterAccountResponse' => '\\RegisterAccountResponse',
    'TransferPartnerType' => '\\TransferPartnerType',
    'PurchaseMilesRequest' => '\\PurchaseMilesRequest',
    'PurchaseMilesResponse' => '\\PurchaseMilesResponse',
    'PurchaseRatesRequest' => '\\PurchaseRatesRequest',
    'PurchaseRatesResponse' => '\\PurchaseRatesResponse',
    'ProviderPurchaseRatesType' => '\\ProviderPurchaseRatesType',
    'PurchaseRateType' => '\\PurchaseRateType',);

  /**
   * 
   * @param array $options A array of config values
   * @param string $wsdl The wsdl file to use
   * @access public
   */
  public function __construct(array $options = array(), $wsdl = 'services.wsdl')
  {
    foreach (self::$classmap as $key => $value) {
      if (!isset($options['classmap'][$key])) {
        $options['classmap'][$key] = $value;
      }
    }
    
    parent::__construct($wsdl, $options);
  }

  /**
   * Check single accounts
   * 
   * @param CheckAccountRequest $body
   * @access public
   * @return CheckAccountResponse
   */
  public function CheckAccount(CheckAccountRequest $body)
  {
    getSymfonyContainer()->get("logger")->critical('Wsdl call', ['AccountID' => $body->AccountID]);
    return $this->__soapCall('CheckAccount', array($body));
  }

  /**
   * Check multiple accounts
   *
   * @param CheckAccountsRequest $body
   * @access public
   * @return CheckAccountsResponse
   */
  public function CheckAccounts(CheckAccountsRequest $body)
  {
    getSymfonyContainer()->get("logger")->critical('Wsdl call package');
    return $this->__soapCall('CheckAccounts', array($body));
  }

  /**
   * 
   * @param ListProvidersRequest $body
   * @access public
   * @return ListProvidersResponse
   */
  public function ListProviders(ListProvidersRequest $body)
  {
    return $this->__soapCall('ListProviders', array($body));
  }

  /**
   * auto-login
   * 
   * @param PrepareRedirectRequest $body
   * @access public
   * @return PrepareRedirectResponse
   */
  public function PrepareRedirect(PrepareRedirectRequest $body)
  {
    return $this->__soapCall('PrepareRedirect', array($body));
  }

  /**
   * accepts results of asynchronous account check. this method is for reference only. it should not be called, it should be implemented on your side
   * 
   * @param CheckAccountResponse $body
   * @access public
   * @return CheckAccountCallbackResponse
   */
  public function CheckAccountCallback(CheckAccountResponse $body)
  {
    return $this->__soapCall('CheckAccountCallback', array($body));
  }

  /**
   * accepts results of asynchronous account check. this method is for reference only. it should not be called, it should be implemented on your side
   * 
   * @param CheckAccountsResponse $body
   * @access public
   * @return CheckAccountCallbackResponse
   */
  public function CheckAccountsCallback(CheckAccountsResponse $body)
  {
    return $this->__soapCall('CheckAccountsCallback', array($body));
  }

  /**
   * get info about your queue
   * 
   * @param PartnerType $body
   * @access public
   * @return QueueInfo
   */
  public function GetQueueInfo(PartnerType $body)
  {
    return $this->__soapCall('GetQueueInfo', array($body));
  }

  /**
   * 
   * @param CheckConfirmationNumberRequest $body
   * @access public
   * @return CheckConfirmationNumberResponse
   */
  public function CheckConfirmationNumber(CheckConfirmationNumberRequest $body)
  {
    return $this->__soapCall('CheckConfirmationNumber', array($body));
  }

  /**
   * transfer miles
   * 
   * @param TransferMilesRequest $body
   * @access public
   * @return TransferMilesResponse
   */
  public function TransferMiles(TransferMilesRequest $body)
  {
    return $this->__soapCall('TransferMiles', array($body));
  }

  /**
   * register new account
   *
   * @param RegisterAccountRequest $body
   * @access public
   * @return RegisterAccountResponse
   */
  public function RegisterAccount(RegisterAccountRequest $body)
  {
    return $this->__soapCall('RegisterAccount', array($body));
  }

  /**
  * register new account
  *
  * @param PurchaseMilesRequest $body
  * @access public
  * @return PurchaseMilesResponse
  */
  public function PurchaseMiles(PurchaseMilesRequest $body)
  {
    return $this->__soapCall('PurchaseMiles', array($body));
  }

  /**
   *
   * @param PurchaseRatesRequest $body
   * @access public
   * @return PurchaseRatesResponse
   */
  public function PurchaseRates(PurchaseRatesRequest $body)
  {
      return $this->__soapCall('PurchaseRates', array($body));
  }

}
