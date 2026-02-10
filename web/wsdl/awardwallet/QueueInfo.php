<?php

class QueueInfo
{

  /**
   * 
   * @var int $APIVersion
   * @access public
   */
  public $APIVersion = null;

  /**
   * 
   * @var int $QueueSize
   * @access public
   */
  public $QueueSize = null;

  /**
   * 
   * @param int $APIVersion
   * @param int $QueueSize
   * @access public
   */
  public function __construct($APIVersion, $QueueSize)
  {
    $this->APIVersion = $APIVersion;
    $this->QueueSize = $QueueSize;
  }

}
