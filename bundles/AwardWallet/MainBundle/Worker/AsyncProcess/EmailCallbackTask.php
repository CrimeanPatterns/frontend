<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Globals\StringUtils;

class EmailCallbackTask extends Task
{
    private string $serializedData;

    public function __construct(string $serializedData)
    {
        parent::__construct(EmailCallbackExecutor::class, StringUtils::getRandomCode(20));
        $this->serializedData = $serializedData;
    }

    public function getSerializedData(): string
    {
        return $this->serializedData;
    }

    public function getMaxRetriesCount(): int
    {
        return 1;
    }
}
