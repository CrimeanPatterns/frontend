<?php

class SavePasswordRequest
{

  /**
   * 
   * @var string $Partner
   * @access public
   */
  public $Partner;

  /**
   *
   * @var string $Provider
   * @access public
   */
  public $Provider;

  /**
   * 
   * @var string $Login
   * @access public
   */
  public $Login;

  /**
   * 
   * @var string $Login2
   * @access public
   */
  public $Login2;

  /**
   * 
   * @var string $Login3
   * @access public
   */
  public $Login3;

  /**
   * 
   * @var string $Password
   * @access public
   */
  public $Password;

  /**
   *
   * @var array $Answers
   * @access public
   */
  public $Answers;

  /**
   * 
   * @param string $Partner
   * @param string $Provider
   * @param string $Login
   * @param string $Login2
   * @param string $Login3
   * @param string $Password
   * @param string $Answers
   * @access public
   */
  public function __construct($Partner, $Provider, $Login, $Login2, $Login3, $Password, $Answers)
  {
    $this->Partner = $Partner;
    $this->Provider = $Provider;
    $this->Login = $Login;
    $this->Login2 = $Login2;
    $this->Login3 = $Login3;
    $this->Password = $Password;
    $this->Answers = $Answers;
  }

}
