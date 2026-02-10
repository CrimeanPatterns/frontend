<?php

class RequestAccountPasswordRequest
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
   * @var string $Note
   * @access public
   */
  public $Note;

  /**
   *
   * @var boolean $Delete
   * @access public
   */
  public $Delete;

  /**
   * 
   * @param string $Partner
   * @param string $Provider
   * @param string $Login
   * @param string $Note
   * @param boolean $Delete
   * @access public
   */
  public function __construct($Partner, $Provider, $Login, $Note, $Delete)
  {
    $this->Partner = $Partner;
    $this->Provider = $Provider;
    $this->Login = $Login;
    $this->Note = $Note;
    $this->Delete = $Delete;
  }

}
