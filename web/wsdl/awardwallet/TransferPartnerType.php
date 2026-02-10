<?php

class TransferPartnerType {

	/**
	 *
	 * @var string $SourceProvider
	 * @access public
	 */
	public $SourceProvider;

	/**
	 *
	 * @var string $TargetProvider
	 * @access public
	 */
	public $TargetProvider;

	/**
	 *
	 * @var string $Rate
	 * @access public
	 */
	public $Rate;

	/**
	 *
	 * @var string $Duration
	 * @access public
	 */
	public $Duration;

	/**
	 *
	 * @var string $Comment
	 * @access public
	 */
	public $Comment;

	/**
	 *
	 * @param string $SourceProvider
	 * @param string $TargetProvider
	 * @param string $Rate
	 * @param string $Duration
	 * @param string $Comment
	 * @access public
	 */
	public function __construct($SourceProvider, $TargetProvider, $Rate, $Duration, $Comment) {
		$this->SourceProvider = $SourceProvider;
		$this->TargetProvider = $TargetProvider;
		$this->Rate = $Rate;
		$this->Duration = $Duration;
		$this->Comment = $Comment;
	}

} 