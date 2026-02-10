<?php

namespace AwardWallet\MainBundle\Service\AppBot\Adapter;

/**
 * Interface AdapterInterface.
 */
interface AdapterInterface
{
    /**
     * @param string|array $message
     */
    public function send(string $channelName, $message, ?string $botname = null, ?string $icon = null): bool;
}
