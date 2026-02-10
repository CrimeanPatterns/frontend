<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class SearchTask extends Task
{
    private int $queryId;

    public function __construct(int $queryId)
    {
        parent::__construct(SearchExecutor::class, bin2hex(random_bytes(10)));

        $this->queryId = $queryId;
    }

    public function getQueryId(): int
    {
        return $this->queryId;
    }
}
