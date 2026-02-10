<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class ExpirationTask extends Task
{
    public $providerId;
    public $rawData;

    public function __construct($providerId, $requestId, $rawData)
    {
        parent::__construct("aw.async.executor.expiration", $requestId);
        $this->providerId = $providerId;
        $this->rawData = $rawData;
    }
}
