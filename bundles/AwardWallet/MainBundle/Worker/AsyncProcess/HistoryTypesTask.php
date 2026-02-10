<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class HistoryTypesTask extends Task
{
    public $providerId;
    public $redemption;
    public $mapper;
    public $rawData;
    public $filter;
    public $firstColumnTitle;

    public function __construct($providerId, $redemption, $mapper, $requestId, $rawData, $filter, $firstColumnTitle)
    {
        parent::__construct("aw.async.executor.history_types", $requestId);
        $this->providerId = $providerId;
        $this->redemption = $redemption;
        $this->mapper = $mapper;
        $this->rawData = $rawData;
        $this->filter = $filter;
        $this->firstColumnTitle = $firstColumnTitle;
    }
}
