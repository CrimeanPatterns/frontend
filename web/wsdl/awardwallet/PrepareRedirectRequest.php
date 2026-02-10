<?php

class PrepareRedirectRequest
{

  /**
   * 
   * @var int $APIVersion
   * @access public
   */
  public $APIVersion = null;

  /**
   * 
   * @var string $Provider
   * @access public
   */
  public $Provider = null;

  /**
   * 
   * @var string $Login
   * @access public
   */
  public $Login = null;

  /**
   * 
   * @var string $Login2
   * @access public
   */
  public $Login2 = null;

  /**
   * 
   * @var string $Login3
   * @access public
   */
  public $Login3 = null;

  /**
   * 
   * @var string $Password
   * @access public
   */
  public $Password = null;

  /**
   * 
   * @var string $TargetURL
   * @access public
   */
  public $TargetURL = null;

  /**
   * 
   * @var string $UserID
   * @access public
   */
  public $UserID = null;

  /**
   * 
   * @var string $TargetType
   * @access public
   */
  public $TargetType = null;

  /**
   * 
   * @var boolean $AllowHTTPS
   * @access public
   */
  public $AllowHTTPS = null;

  /**
   *
   * @var string $StartURL
   * @access public
   */
  public $StartURL = null;

  /**
   * 
   * @param int $APIVersion
   * @param string $Provider
   * @param string $Login
   * @param string $Login2
   * @param string $Login3
   * @param string $Password
   * @param string $TargetURL
   * @param string $UserID
   * @param string $TargetType
   * @param boolean $AllowHTTPS
   * @access public
   */
  public function __construct($APIVersion, $Provider, $Login, $Login2, $Login3, $Password, $TargetURL, $UserID, $TargetType, $AllowHTTPS, $StartURL = null)
  {
    $this->APIVersion = $APIVersion;
    $this->Provider = $Provider;
    $this->Login = $Login;
    $this->Login2 = $Login2;
    $this->Login3 = $Login3;
    $this->Password = $Password;
    $this->TargetURL = $TargetURL;
    $this->UserID = $UserID;
    $this->TargetType = $TargetType;
    $this->AllowHTTPS = $AllowHTTPS;
    $this->StartURL = $StartURL;
  }

}
