<?php

namespace AwardWallet\MainBundle\Service\User\Async;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class FareDropUsersTask extends Task
{
    public string $responseChannel;
    public array $hashes;

    public function __construct(
        string $responseChannel = '',
        ?array $hashes = null
    ) {
        parent::__construct(FareDropUsersExecutor::class, bin2hex(random_bytes(10)));

        $this->responseChannel = $responseChannel;
        $this->hashes = $hashes;
    }

    public function getResponseChannel(): string
    {
        return $this->responseChannel;
    }

    public function getHashes(): array
    {
        return $this->hashes;
    }
}
