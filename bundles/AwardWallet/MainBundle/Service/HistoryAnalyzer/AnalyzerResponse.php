<?php

namespace AwardWallet\MainBundle\Service\HistoryAnalyzer;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class AnalyzerResponse
{
    public $volumeOfPoints;
    public $transactionFrequency;
    public $transactionRecency;
    public $rows = [];
    public $excluded = 0;

    public function __construct()
    {
        $this->volumeOfPoints = new ResponseDetails();
        $this->transactionFrequency = new ResponseDetails();
        $this->transactionRecency = new ResponseDetails();
    }
}
