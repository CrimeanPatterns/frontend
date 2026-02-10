<?php

class FileType
{

  /**
   * 
   * @var date $Date
   * @access public
   */
  public $Date = null;

  /**
   * 
   * @var string $Name
   * @access public
   */
  public $Name = null;

  /**
   * 
   * @var string $Extension
   * @access public
   */
  public $Extension = null;

  /**
   * 
   * @var string $Kind
   * @access public
   */
  public $Kind = null;

  /**
   * 
   * @var string $AccountNumber
   * @access public
   */
  public $AccountNumber = null;

  /**
   * 
   * @var string $AccountName
   * @access public
   */
  public $AccountName = null;

  /**
   * 
   * @var string $AccountType
   * @access public
   */
  public $AccountType = null;

  /**
   * 
   * @var string $Contents
   * @access public
   */
  public $Contents = null;

  /**
   * 
   * @param date $Date
   * @param string $Name
   * @param string $Extension
   * @param string $Kind
   * @param string $AccountNumber
   * @param string $AccountName
   * @param string $AccountType
   * @param string $Contents
   * @access public
   */
  public function __construct($Date, $Name, $Extension, $Kind, $AccountNumber, $AccountName, $AccountType, $Contents)
  {
    $this->Date = $Date;
    $this->Name = $Name;
    $this->Extension = $Extension;
    $this->Kind = $Kind;
    $this->AccountNumber = $AccountNumber;
    $this->AccountName = $AccountName;
    $this->AccountType = $AccountType;
    $this->Contents = $Contents;
  }

}
