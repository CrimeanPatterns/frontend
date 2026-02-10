<?php

namespace AwardWallet\MainBundle\Globals\BinarySearchResult;

final class GreaterThan implements BinarySearchResultInterface
{
    private int $prefix;

    public function __construct(int $prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefixLength(): int
    {
        return $this->prefix;
    }

    public function getSuffixLength(): int
    {
        return 0;
    }
}
