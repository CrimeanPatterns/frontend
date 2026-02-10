<?php

namespace AwardWallet\MainBundle\Service;

interface OutboxProcessorInterface
{
    public function process(array $outboxItem): void;

    /**
     * @return int[]
     */
    public static function getSupportedOutboxTypes(): array;
}
