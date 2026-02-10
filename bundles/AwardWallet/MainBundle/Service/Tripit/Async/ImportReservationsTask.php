<?php

namespace AwardWallet\MainBundle\Service\Tripit\Async;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class ImportReservationsTask extends Task
{
    private string $requestBody;

    public function __construct(string $requestBody)
    {
        parent::__construct(ImportReservationsExecutor::class, bin2hex(random_bytes(10)));
        $this->requestBody = $requestBody;
    }

    public function getRequestBody(): string
    {
        return $this->requestBody;
    }
}
