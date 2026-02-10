<?php

namespace AwardWallet\MainBundle\Service\CreditCards\MerchantRecalculator;

class Task extends \AwardWallet\MainBundle\Worker\AsyncProcess\Task
{
    private array $merchantPatternIds;

    public function __construct(array $merchantPatternIds)
    {
        parent::__construct(Executor::class, bin2hex(random_bytes(10)));

        $this->merchantPatternIds = $merchantPatternIds;
    }

    public function getMerchantPatternIds(): array
    {
        return $this->merchantPatternIds;
    }

    public function getMaxRetriesCount(): int
    {
        return 10;
    }
}
