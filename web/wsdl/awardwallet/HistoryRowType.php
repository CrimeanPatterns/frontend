<?php

class HistoryRowType
{

  /**
   * 
   * @var HistoryFieldType $Fields
   * @access public
   */
  public $Fields = null;

  /**
   * 
   * @param HistoryFieldType $Fields
   * @access public
   */
  public function __construct($Fields)
  {
    $this->Fields = $Fields;
  }

}
