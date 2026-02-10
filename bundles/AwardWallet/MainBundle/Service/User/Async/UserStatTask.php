<?php

namespace AwardWallet\MainBundle\Service\User\Async;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class UserStatTask extends Task
{
    public string $responseChannel;
    public array $conditions;

    public function __construct(
        string $responseChannel = '',
        ?array $conditions = null
    ) {
        parent::__construct(UserStatExecutor::class, bin2hex(random_bytes(10)));

        $this->responseChannel = $responseChannel;
        $this->conditions = $conditions;
    }

    public function getResponseChannel(): string
    {
        return $this->responseChannel;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }
}
