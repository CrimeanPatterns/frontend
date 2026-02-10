<?php

class ProviderInfoType
{

  /**
   * 
   * @var int $Kind
   * @access public
   */
  public $Kind = null;

  /**
   * 
   * @var string $Code
   * @access public
   */
  public $Code = null;

  /**
   * 
   * @var string $DisplayName
   * @access public
   */
  public $DisplayName = null;

  /**
   * 
   * @var string $ProviderName
   * @access public
   */
  public $ProviderName = null;

  /**
   * 
   * @var string $ProgramName
   * @access public
   */
  public $ProgramName = null;

  /**
   * 
   * @var InputType $Login
   * @access public
   */
  public $Login = null;

  /**
   * 
   * @var InputType $Login2
   * @access public
   */
  public $Login2 = null;

  /**
   * 
   * @var InputType $Login3
   * @access public
   */
  public $Login3 = null;

  /**
   * 
   * @var InputType $Password
   * @access public
   */
  public $Password = null;

  /**
   * 
   * @var PropertyInfoType $Properties
   * @access public
   */
  public $Properties = null;

  /**
   * 
   * @var boolean $AutoLogin
   * @access public
   */
  public $AutoLogin = null;

  /**
   * 
   * @var boolean $DeepLinking
   * @access public
   */
  public $DeepLinking = null;

  /**
   * 
   * @var boolean $CanCheckConfirmation
   * @access public
   */
  public $CanCheckConfirmation = null;

  /**
   * 
   * @var boolean $CanCheckItinerary
   * @access public
   */
  public $CanCheckItinerary = null;

  /**
   * 
   * @var int $CanCheckExpiration
   * @access public
   */
  public $CanCheckExpiration = null;

  /**
   * 
   * @var InputType $ConfirmationNumberFields
   * @access public
   */
  public $ConfirmationNumberFields = null;

  /**
   * 
   * @var PropertyInfoType $HistoryColumns
   * @access public
   */
  public $HistoryColumns = null;

  /**
   * 
   * @var int $EliteLevelsCount
   * @access public
   */
  public $EliteLevelsCount = null;

  /**
   * 
   * @var boolean $CanParseHistory
   * @access public
   */
  public $CanParseHistory = null;

  /**
   *
   * @var boolean $CanParseFiles
   * @access public
   */
  public $CanParseFiles = null;

  /**
  *
  * @var boolean $CanTransferRewards
  * @access public
  */
  public $CanTransferRewards = null;

  /**
  * @var TransferPartnerType $TransferRewardsPartners
  * @access public
  */
  public $TransferRewardsPartners = null;

  /**
  *
  * @var boolean $CanRegisterAccount
  * @access public
  */
  public $CanRegisterAccount = null;

  /**
  *
  * @var boolean $CanBuyMiles
  * @access public
  */
  public $CanBuyMiles = null;

  /**
  *
  * @var InputType $RegisterAccountFields
  * @access public
  */
  public $RegisterAccountFields = null;

  /**
  *
  * @var InputType $PurchaseMilesFields
  * @access public
  */
  public $PurchaseMilesFields = null;

  /**
   * 
   * @param int $Kind
   * @param string $Code
   * @param string $DisplayName
   * @param string $ProviderName
   * @param string $ProgramName
   * @param InputType $Login
   * @param InputType $Login2
   * @param InputType $Login3
   * @param InputType $Password
   * @param PropertyInfoType $Properties
   * @param boolean $AutoLogin
   * @param boolean $DeepLinking
   * @param boolean $CanCheckConfirmation
   * @param boolean $CanCheckItinerary
   * @param int $CanCheckExpiration
   * @param InputType $ConfirmationNumberFields
   * @param PropertyInfoType $HistoryColumns
   * @param int $EliteLevelsCount
   * @param boolean $CanParseHistory
   * @param boolean $CanParseFiles
   * @param boolean $CanTransferRewards
   * @param boolean $CanRegisterAccount
   * @param boolean $CanBuyMiles
   * @param InputType $RegisterAccountFields
   * @param InputType $PurchaseMilesFields
   * @access public
   */
  public function __construct($Kind, $Code, $DisplayName, $ProviderName, $ProgramName, $Login, $Login2, $Login3, $Password, $Properties, $AutoLogin, $DeepLinking, $CanCheckConfirmation, $CanCheckItinerary, $CanCheckExpiration, $ConfirmationNumberFields, $HistoryColumns, $EliteLevelsCount, $CanParseHistory, $CanParseFiles, $CanTransferRewards, $TransferRewardsPartners, $CanRegisterAccount, $CanBuyMiles, $RegisterAccountFields, $PurchaseMilesFields)
  {
    $this->Kind = $Kind;
    $this->Code = $Code;
    $this->DisplayName = $DisplayName;
    $this->ProviderName = $ProviderName;
    $this->ProgramName = $ProgramName;
    $this->Login = $Login;
    $this->Login2 = $Login2;
    $this->Login3 = $Login3;
    $this->Password = $Password;
    $this->Properties = $Properties;
    $this->AutoLogin = $AutoLogin;
    $this->DeepLinking = $DeepLinking;
    $this->CanCheckConfirmation = $CanCheckConfirmation;
    $this->CanCheckItinerary = $CanCheckItinerary;
    $this->CanCheckExpiration = $CanCheckExpiration;
    $this->ConfirmationNumberFields = $ConfirmationNumberFields;
    $this->HistoryColumns = $HistoryColumns;
    $this->EliteLevelsCount = $EliteLevelsCount;
    $this->CanParseHistory = $CanParseHistory;
    $this->CanParseFiles = $CanParseFiles;
    $this->CanTransferRewards = $CanTransferRewards;
    $this->TransferRewardsPartners = $TransferRewardsPartners;
    $this->CanRegisterAccount = $CanRegisterAccount;
    $this->CanBuyMiles = $CanBuyMiles;
    $this->RegisterAccountFields = $RegisterAccountFields;
    $this->PurchaseMilesFields = $PurchaseMilesFields;
  }

}
