<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\Async;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

/**
 * @NoDI()
 */
class RelinkMerchantsTask extends Task
{
    private string $responseChannel;

    public function __construct(string $responseChannel)
    {
        parent::__construct(RelinkMerchantsExecutor::class, StringUtils::getRandomCode(20));

        $this->responseChannel = $responseChannel;
    }

    public function getMaxRetriesCount(): int
    {
        return 1;
    }

    public function getResponseChannel(): string
    {
        return $this->responseChannel;
    }
}
