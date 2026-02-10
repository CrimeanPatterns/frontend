<?php

namespace AwardWallet\MainBundle\Service\Cache;

use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Store\MemcachedStore;

class AwaitTrackedMemcachedStore extends MemcachedStore
{
    private int $keySaveAttempts = 0;

    public function save(Key $key)
    {
        $this->keySaveAttempts++;

        parent::save($key);
    }

    public function wasAwaited(): bool
    {
        return $this->keySaveAttempts > 1;
    }
}
