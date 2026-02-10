<?php

namespace AwardWallet\MainBundle\Timeline\FilterCallback;

interface FilterCallbackInterface
{
    public function getCacheKey(): string;

    public function getCallback(): callable;

    public function and(self $filterCallback): self;
}
