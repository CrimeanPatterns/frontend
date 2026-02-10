<?php

namespace AwardWallet\MobileBundle\Form\Type\Helpers\OrderBuilder\Matcher;

interface MapMatcherInterface
{
    /**
     * @param array<array-key, int> $map
     * @return list<int> range of indices
     */
    public function matchMap(array $map): array;
}
