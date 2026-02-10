<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Async;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

/**
 * @NoDI()
 */
class TransactionsExistTask extends Task
{
    private array $subAccountIds;

    private string $responseChannel;

    public function __construct(array $subAccountPatternIds, string $responseChannel)
    {
        parent::__construct(TransactionsExistExecutor::class, bin2hex(random_bytes(10)));
        $this->subAccountIds = $subAccountPatternIds;
        $this->responseChannel = $responseChannel;
    }

    public function getSubAccountIds(): array
    {
        return $this->subAccountIds;
    }

    public function getResponseChannel(): string
    {
        return $this->responseChannel;
    }
}
