<?php

class AnswerType
{

  /**
   * 
   * @var string $Question
   * @access public
   */
  public $Question = null;

  /**
   * 
   * @var string $Answer
   * @access public
   */
  public $Answer = null;

  /**
   * 
   * @param string $Question
   * @param string $Answer
   * @access public
   */
  public function __construct($Question, $Answer)
  {
    $this->Question = $Question;
    $this->Answer = $Answer;
  }

}
